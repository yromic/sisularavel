<?php

namespace App\Services\Bulk;

use App\Http\Requests\BulkDeleteRequest;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;

class LetterBulkService extends BulkDeleteService
{
    public function previewDeleteIncoming(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateLetterAction($request, $actor, IncomingLetter::query(), false, 'delete');
    }

    public function bulkDeleteIncoming(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateLetterAction($request, $actor, IncomingLetter::query(), true, 'delete', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'LETTER_INCOMING_BULK_DELETED',
                $actor,
                IncomingLetter::class,
                $result->deletedCount(),
                "Menghapus massal {$result->deletedCount()} arsip surat masuk.",
                ['deleted' => $result->deleted, 'skipped_count' => $result->skippedCount()]
            );
        }

        return $result;
    }

    public function previewRestoreIncoming(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateLetterAction($request, $actor, IncomingLetter::onlyTrashed(), false, 'restore');
    }

    public function bulkRestoreIncoming(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateLetterAction($request, $actor, IncomingLetter::onlyTrashed(), true, 'restore', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'LETTER_INCOMING_BULK_RESTORED',
                $actor,
                IncomingLetter::class,
                $result->deletedCount(),
                "Memulihkan massal {$result->deletedCount()} arsip surat masuk.",
                ['restored' => $result->deleted, 'skipped_count' => $result->skippedCount()]
            );
        }

        return $result;
    }

    public function previewDeleteOutgoing(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateLetterAction($request, $actor, OutgoingLetter::query(), false, 'delete');
    }

    public function bulkDeleteOutgoing(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateLetterAction($request, $actor, OutgoingLetter::query(), true, 'delete', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'LETTER_OUTGOING_BULK_DELETED',
                $actor,
                OutgoingLetter::class,
                $result->deletedCount(),
                "Menghapus massal {$result->deletedCount()} arsip surat keluar.",
                ['deleted' => $result->deleted, 'skipped_count' => $result->skippedCount()]
            );
        }

        return $result;
    }

    public function previewRestoreOutgoing(BulkDeleteRequest $request, User $actor): BulkDeleteResult
    {
        return $this->evaluateLetterAction($request, $actor, OutgoingLetter::onlyTrashed(), false, 'restore');
    }

    public function bulkRestoreOutgoing(BulkDeleteRequest $request, User $actor, AuditLogger $auditLogger): BulkDeleteResult
    {
        $result = $this->evaluateLetterAction($request, $actor, OutgoingLetter::onlyTrashed(), true, 'restore', $auditLogger);

        if ($result->deletedCount() > 0) {
            $auditLogger->bulkEvent(
                'LETTER_OUTGOING_BULK_RESTORED',
                $actor,
                OutgoingLetter::class,
                $result->deletedCount(),
                "Memulihkan massal {$result->deletedCount()} arsip surat keluar.",
                ['restored' => $result->deleted, 'skipped_count' => $result->skippedCount()]
            );
        }

        return $result;
    }

    protected function evaluateLetterAction(
        BulkDeleteRequest $request,
        User $actor,
        Builder $baseQuery,
        bool $execute,
        string $action, // 'delete' | 'restore'
        ?AuditLogger $auditLogger = null
    ): BulkDeleteResult {
        abort_unless(in_array($actor->role, ['super_admin', 'division_admin'], true), 403, 'Operator tidak memiliki wewenang mengelola arsip surat.');

        if ($actor->role !== 'super_admin') {
            $baseQuery->where('division_id', $actor->division_id);
        }

        $ids = $this->resolveTargetIds($request, $baseQuery);

        // Strict cross-division check
        if ($actor->role !== 'super_admin') {
            $modelClass = $baseQuery->getModel()::class;
            $foreignExists = $modelClass::withTrashed()
                ->whereIn('id', $ids)
                ->where('division_id', '!=', $actor->division_id)
                ->exists();

            if ($foreignExists) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
            }
        }

        $letters = $baseQuery->whereIn('id', $ids)->get();
        $result = new BulkDeleteResult;

        if ($execute) {
            $letterMap = $letters->keyBy('id');

            $this->processChunks($ids, function (array $chunk) use ($letterMap, $actor, $action, $auditLogger, $result): void {
                foreach ($chunk as $id) {
                    /** @var IncomingLetter|OutgoingLetter|null $letter */
                    $letter = $letterMap->get($id);
                    if ($letter) {
                        if ($action === 'delete') {
                            $letter->forceFill(['deleted_by_user_id' => $actor->id])->save();
                            $letter->delete();

                            if ($letter instanceof IncomingLetter) {
                                $auditLogger?->incomingLetterEvent('LETTER_INCOMING_DELETED', $actor, $letter, 'Incoming letter soft deleted (bulk).');
                            } else {
                                $auditLogger?->outgoingLetterEvent('LETTER_OUTGOING_DELETED', $actor, $letter, 'Outgoing letter soft deleted (bulk).');
                            }
                        } else {
                            $letter->restore();
                            $letter->forceFill(['deleted_by_user_id' => null])->save();

                            if ($letter instanceof IncomingLetter) {
                                $auditLogger?->incomingLetterEvent('LETTER_INCOMING_RESTORED', $actor, $letter, 'Incoming letter restored (bulk).');
                            } else {
                                $auditLogger?->outgoingLetterEvent('LETTER_OUTGOING_RESTORED', $actor, $letter, 'Outgoing letter restored (bulk).');
                            }
                        }

                        $result->addDeleted($letter->id);
                    }
                }
            });
        } else {
            foreach ($letters as $letter) {
                $result->addDeleted($letter->id);
            }
        }

        return $result;
    }
}
