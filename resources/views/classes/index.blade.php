<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Classes List</title>
</head>
<body>
    <h1>Classes</h1>
    <a href="{{ route('classes.create') }}">Create New Class</a>
    <ul>
        @foreach ($classes as $class)
            <li>{{ $class->name }}</li>
        @endforeach
    </ul>
</body>
</html>
