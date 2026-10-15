<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Lampiran Surat' }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: a4 portrait;
        }
        html, body {
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            font-family: sans-serif;
        }
        .page-container {
            width: 100%;
            height: 100%;
            page-break-after: always;
            text-align: center;
            vertical-align: middle;
            background-color: #ffffff;
        }
        .page-container:last-child {
            page-break-after: avoid;
        }
        .photo-img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            margin: 0 auto;
            display: block;
        }
    </style>
</head>
<body>
    @foreach ($photos as $photoData)
        <div class="page-container">
            <img src="{{ $photoData }}" class="photo-img">
        </div>
    @endforeach
</body>
</html>
