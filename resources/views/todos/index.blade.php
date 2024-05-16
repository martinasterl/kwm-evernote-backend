<!DOCTYPE html>
<html>
<head>
    <title>
        Laravel
    </title>
</head>
<body>
<h1>Todos</h1>
<ul>
    @foreach($todos as $todo)
        <li><a href="todos/{{$todo->id}}">{{$todo->title}}</a></li>
    @endforeach
</ul>
</body>
</html>

