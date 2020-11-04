<?php
function game_recommend()
{
    global $tmpl;
    $html =<<<HTML
    <div id="game-recommend-container" class="swiper-container">
        <div id="game-recommend" class="swiper-wrapper recommend_list_content">
        </div>
    </div>
    <script>
    var game_recommend_swiper = new Swiper('#game-recommend-container', {
        slidesPerView: 3.55,
        spaceBetween: 5,
    });
    </script>
HTML;

  return $html;
}
?>