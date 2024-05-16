<!DOCTYPE html>
<html>
<head>
    <title>
        Laravel
    </title>
</head>
<body>
<h1>Todos und Listen</h1>
    <h2><a href="todos">Todos</a></h2>
    <ul>
        @foreach($todos as $todo)
            <li><a href="todos/{{$todo->id}}">{{$todo->title}}</a></li>
        @endforeach
    </ul>
    </br>
    <h2><a href="lists">Listen</a></h2>
    <ul>
        @foreach($lists as $list)
            <li><a href="lists/{{$list->id}}">{{$list->name}}, erstellt am {{$list->created_at}}</a></li>
        @endforeach
    </ul>
</body>
</html>
