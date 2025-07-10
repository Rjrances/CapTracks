<!-- resources/views/classes/create.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Class</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet" /> <!-- If using Tailwind or other CSS -->
</head>
<body class="p-6">

    <h1 class="text-2xl font-bold mb-4">Create New Class</h1>

    <form action="{{ route('classes.store') }}" method="POST">
        @csrf
        <div>
            <label for="name" class="block font-medium mb-1">Class Name</label>
            <input type="text" name="name" id="name" class="border p-2 w-full" required>
        </div>

        <button type="submit" class="mt-4 bg-blue-600 text-white py-2 px-4 rounded">
            Save Class
        </button>
    </form>

</body>
</html>
