<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>SteamTools</title>

    <!-- Bootstrap -->
    <link href="/css/bootstrap.css" rel="stylesheet">
    <link href="/css/bootstrap_overrides.css" rel="stylesheet">
    <link href="/css/sticky-footer.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        @yield('page_style')
    </style>
  </head>
  <body id="bootstrap-overrides">
    <div class="jumbotron text-center">
      <h1><a href="suggest">SteamTools</a></h1>
      <p>too many games, nothing to play</p> 
    </div>

    <div class="container">
      @yield('content')
    </div>

    <footer class="footer">
      <div class="container">
        <p class="text-muted">
        <a href="http://megmitchell.ca">Meg Mitchell</a> &#9830; 
        <a href="legal">CYA</a> &#9830; 
        Powered by the <a href="https://steamcommunity.com/dev">Steam Web API</a></p>
      </div>
    </footer>


    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="/js/bootstrap.min.js"></script>
    <script src="/js/jquery.matchHeight-min.js"></script>
    <script type="text/javascript">
        @yield('page_script')
    </script>
  </body>
</html>