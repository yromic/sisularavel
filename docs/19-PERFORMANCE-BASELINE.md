# 19. PERFORMANCE BASELINE

## 1. Scope & Objective

This document preserves the verified empirical performance benchmarks and measurement methodology recorded during legacy testing of the Google Apps Script SISURAT backend (`laporan_pengujian_bab43.json` and `test_api_bab43.ps1`). It establishes the scientific baseline against which the future Laravel system (initial baseline: Laravel 12 / PHP 8.2) will be evaluated.

---

## 2. Verified Legacy Empirical Measurements

### 2.1 Test Environment & Execution Parameters
- **Test Date:** 2026-06-23 03:15:47 WIB
- **Target Backend:** Google Apps Script Web App Deployment (`https://script.google.com/macros/s/.../exec`)
- **Test Runner:** Automated PowerShell API Test Suite (`test_api_bab43.ps1`)
- **Network Context:** Client-side invocation to Google serverless execution infrastructure

### 2.2 Empirical Benchmark Data

| Test ID | Workflow / Operation | Payload / Condition | Expected Result | Observed Result | Latency (ms) | Legacy Source Reference |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-01** | Operator Login | Username `miftah`, Division `KURSISSMP` | Token issued (`session_token`) | `LULUS` (Success) | **7,694.83 ms** (~7.7 s) | `laporan_pengujian_bab43.json:10-23` |
| **TC-02** | Cross-Division Access Attack | Operator `DIKDAS` accessing `PAUD2_surat_masuk` table | Request blocked with `ERR_403_DIVISI` | `DITOLAK` (Blocked) | **11,095.90 ms** (~11.1 s) | `laporan_pengujian_bab43.json:24-33` |
| **TC-03** | Unauthorized Request | API invocation without session token | Request blocked with `ERR_401_SESSION` | `DITOLAK` (Blocked) | **5,403.91 ms** (~5.4 s) | `laporan_pengujian_bab43.json:34-43` |
| **TC-04** | Chunked File Upload (Chunk 0) | 72 KB Base64 chunk to `CacheService` | Chunk stored in cache | `LULUS` (Success) | **4,972.40 ms** (~5.0 s) | `laporan_pengujian_bab43.json:44-53` |
| **TC-05** | Chunked File Upload (Chunk 1) | 72 KB Base64 chunk to `CacheService` | Chunk stored in cache | `LULUS` (Success) | **4,191.46 ms** (~4.2 s) | `laporan_pengujian_bab43.json:54-63` |
| **TC-06** | Finalize Upload to Google Drive | Retrieve chunks from cache, assemble blob, write to Drive | File created with Drive URL | `LULUS` (Success) | **10,187.56 ms** (~10.2 s) | `laporan_pengujian_bab43.json:64-76` |

---

## 3. Analysis of Legacy Performance Bottlenecks

1. **High Cold-Start & Runtime Latency:**
   - Every incoming HTTP request to Google Apps Script spins up a V8 serverless execution container, connects to Google Sheets, loads sheet headers, and parses range matrices. Even a rejected unauthorized request (TC-03) required 5.4 seconds.
   - Cross-division verification (TC-02) required 11.1 seconds because GAS had to resolve division sheet tables and perform dynamic string lookups across multiple sheet tabs.
2. **Cumulative Upload Inefficiency:**
   - Because Google CacheService imposes a 100KB per-item quota, uploads were restricted to 72KB Base64 chunks.
   - Uploading a standard **5 MB PDF document** required approximately:
     $$\frac{5 \times 1024 \text{ KB}}{72 \text{ KB}} \approx 71 \text{ sequential HTTP requests}$$
   - At an average of 4.5 seconds per chunk, plus a 10.2-second finalization phase:
     $$\text{Total Duration} = (71 \times 4.5\text{s}) + 10.2\text{s} \approx 329.7\text{ seconds } (\approx \mathbf{5.5 \text{ minutes}})$$
   - This exposed operators to frequent network dropouts and browser timeout errors during standard daily archival tasks.

---

## 4. Target Comparison & Benchmark Plan for Target Laravel Implementation

A future performance validation run must benchmark the Laravel implementation (initial baseline: Laravel 12 / PHP 8.2) under equivalent scenarios:

### 4.1 Benchmark Protocols
- **Test Scenarios:**
  1. `BENCH-01`: User authentication (`POST /login`).
  2. `BENCH-02`: Cross-division unauthorized access rejection (`GET /api/v1/surat-masuk?division_id=PAUD` by `DIKDAS` user).
  3. `BENCH-03`: Unauthenticated rejection (`GET /api/v1/surat-masuk` without session cookie).
  4. `BENCH-04`: 5 MB PDF multipart file upload and persistence (`POST /api/v1/surat-masuk`).
  5. `BENCH-05`: Letter list retrieval with pagination and search (`GET /api/v1/surat-masuk?page=1&limit=50`).
- **Target Performance Objectives:**

| Metric | Legacy GAS Baseline | Laravel Target Objective | Expected Improvement |
| :--- | :--- | :--- | :--- |
| **Login Latency** | 7,694 ms | < 300 ms (p95) | **~96% faster** |
| **Authorization Rejection** | 11,095 ms | < 100 ms (p95) | **~99% faster** |
| **Unauthenticated Check** | 5,403 ms | < 50 ms (p95) | **~99% faster** |
| **5 MB Document Upload** | ~330,000 ms (5.5 min) | < 3,000 ms (p95) | **~99% faster** |
| **List Query (50 items)** | ~8,000 ms | < 250 ms (p95) | **~97% faster** |
