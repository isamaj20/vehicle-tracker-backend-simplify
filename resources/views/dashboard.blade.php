<!DOCTYPE html>
<html>
<head>
    <title>VTS | Device Dashboard</title>
</head>
<body>
<h1>Device Dashboard</h1>
@foreach($devices as $device)
    <h2>{{ $device->device_name }}</h2>
    <p>Ignition: {{ $device->ignition ? 'ON' : 'OFF' }}</p>
    <p>Last Location:
        @if($device->locations->last())
            Lat: {{ $device->locations->last()->lat }},
            Lon: {{ $device->locations->last()->lon }},
            Speed: {{ $device->locations->last()->speed }}
        @endif
    </p>
    <form method="POST" action="{{ url('/api/device/command/'.$device->device_id) }}">
        @csrf
        <input type="text" name="command" placeholder="#KILL, #START, #STATUS">
        <button type="submit">Send Command</button>
    </form>
@endforeach
</body>
</html>
