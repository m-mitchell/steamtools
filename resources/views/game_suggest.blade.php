@extends('template')

@section('content')
      <p>
        After a few years of Steam sales, you might find that you have a huge library of games and no idea what to play next. Why not find some hidden gems in your existing library?
      </p>

    <p class="row">
      <div class="input-group">
        <input type="text" class="form-control" placeholder="Enter Steam ID Here">
        <span class="input-group-btn">
          <button class="btn btn-default" type="button">
            <span class="glyphicon glyphicon-align-left glyphicon-question-sign" aria-hidden="true"></span>
          </button>
          <button class="btn btn-default" type="button">Search!</button>
        </span>
      </div>
    </p>

    <hr>

    <p class="lead">
      You own these highly-rated games, but you haven't tried them yet:
    </p>
    <p>
        Recent games here
    </p>

    <hr>

    <p class="lead">
      You like these games, but you haven't played them recently:
    </p>
    <p>
        Recent games here
    </p>

    <hr>

    <p class="lead">
      People similar to you enjoyed these games:
    </p>
    <p>
        Recent games here
    </p>


@endsection