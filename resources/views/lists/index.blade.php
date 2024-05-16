<!DOCTYPE html>
<html>
    <head>
        <title>
            Laravel
        </title>
    </head>
    <body>
        <h1>Listen</h1>
        <ul>
            @foreach($lists as $list)
                <li><a href="lists/{{$list->id}}">{{$list->name}}, erstellt am {{$list->created_at}}</a></li>
            @endforeach
        </ul>
    </body>
</html>
