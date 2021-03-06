@extends('template')

@section('page_style')
    .game_image {
        width: 100%;
        border: 2px solid #1b1b1b;
    }

    .game_title {
        overflow:hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size:1.2em;
    }

@endsection

@section('page_script')
  $(function() {
      $('.game_description').matchHeight();
      $('.game_panel').matchHeight();

      $('[data-toggle=tooltip]').tooltip({ trigger: "hover" });
      $('#profile-button').click(function(){
          window.open('https://steamcommunity.com/my/profile', '_blank');
      });
  });
@endsection

@section('content')
<p>
  After a few years of Steam sales, you might find that you have a huge library of games and no idea what to play next. Why not find some hidden gems in your existing library?
</p>

<p class="row">
  {!! Form::open(array('action' => 'GameSuggest', 'method'=>'get')) !!}
  <div class="input-group">
    <input type="text" class="form-control" name="id" placeholder="16-digit Steam ID/vanity URL name" value="{{$id}}">
    <span class="input-group-btn">
      <button id="profile-button" class="btn btn-default" type="button" data-toggle="tooltip" data-placement="bottom" title="Your 16-digit Steam ID or vanity URL name can be found in the URL of your Steam profile." >
        <span class="glyphicon glyphicon-align-left glyphicon-question-sign" aria-hidden="true">

        </span>
      </button>
      <button class="btn btn-default" type="submit">Search!</button>
    </span>
  </div>
  {!! Form::close() !!}
</p>

<hr>
<!--
<p class="lead">
  People similar to you enjoyed these games:
</p>
<div class="row">
    Fave games here
</div>
-->
@if ($error==500)
  <p>Couldn't find results for id "{{$id}}". Check the Steam ID and try again. If this problem persists, please contact the <a href="mailto:{{$settings['admin_email']}}">administrator</a>.</p>

@elseif ($error!=null)
  <p>An unexpected error occured when contacting Steam ({{$error}}). If this problem persists, please contact the <a href="mailto:{{$settings['admin_email']}}">administrator</a>.</p>

@elseif ($app_error!=null)
  <p>{{$app_error}}</p>

@elseif ($id!=null)
    <p class="lead">
      You like these games, but you haven't played them recently:
    </p>
    <div class="row">
        @foreach ($fave_games as $app)
          <div class="col-md-4">
           @include('partials.game_suggest_panel', array('app' => $app))
          </div>
          @if ($loop->index%3==2 && !$loop->last)
            </div>
            <div class="row">
          @endif
        @endforeach
    </div>

    <p class="lead">
      You own these highly-rated games, but you haven't tried them yet:
    </p>
    <div class="row">
        @foreach ($new_games as $app)
          <div class="col-md-4">
           @include('partials.game_suggest_panel', array('app' => $app))
          </div>
          @if ($loop->index%3==2 && !$loop->last)
            </div>
            <div class="row">
          @endif
        @endforeach
    </div>
@endif

@endsection