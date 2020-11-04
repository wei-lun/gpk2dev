<?php
function game_exposure()
{
    global $tmpl;
    $tmpl['extend_js'] .= '<script src="casino/game_exposure.js"></script>';

    $html =<<<HTML

    <div id="game-exposure-container" class="swiper-container">
        <div id="game-exposure" class="swiper-wrapper">
        </div>
    </div>
    <script>
    var game_exposure_swiper = new Swiper('#game-exposure-container', {
        slidesPerView: 3.55,
        spaceBetween: 5,
    });
    </script>
HTML;

  return $html;
}
?>