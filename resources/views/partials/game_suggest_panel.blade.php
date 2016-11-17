
<div class="panel game_panel">
    <div class="panel-body">
        <p class="game_title"><a href="http://steamcommunity.com/app/{{ $app['steam_appid'] }}">{{ $app['title'] }}</a></p>
        <p><a href="http://steamcommunity.com/app/{{ $app['steam_appid'] }}"><img class="game_image" src="{{ $app['image_path'] }}"/></a></p>
        <p class="game_description">{{ $app['description'] }}
        <div class="game_footer">
        <p><b>Reviews:</b> <a href="http://steamcommunity.com/app/{{ $app['steam_appid'] }}/reviews/?browsefilter=toprated">{{ $app['review_score'] }}% Positive</a></p>
        </div>
    </div>
</div>