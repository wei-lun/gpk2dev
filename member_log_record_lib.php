<?php

// 取最近10筆登入資料
function get_user_log(){

    $sql=<<<SQL
    SELECT * 
        FROM root_memberlog 
            WHERE who = '{$_SESSION['member']->account}'
            AND sub_service = 'login'
            AND site = 'f'
                ORDER BY occurtime DESC 
                LIMIT 10
SQL;

    return $sql;
}


// 時間差
function convert_to_fuzzy_time($times){
    global $tr;

    date_default_timezone_set('America/St_Thomas');
    $unix   = strtotime($times);
    $now    = time();
    $diff_sec   = $now - $unix;

    if($diff_sec < 60){
        $time   = $diff_sec;
        //秒前
        $unit   = $tr['second ago'];
    }
    elseif($diff_sec < 3600){
        $time   = $diff_sec/60;
        //分鐘前
        $unit   = $tr['minute ago'];
    }
    elseif($diff_sec < 86400){
        $time   = $diff_sec/3600;
        //小時前
        $unit   = $tr['hour ago'];
    }
    elseif($diff_sec < 2764800){
        $time   = $diff_sec/86400;
        //天前
        $unit   = $tr['day ago'];
    }
    elseif($diff_sec < 31536000){
        $time   = $diff_sec/2592000;
        //月前
        $unit   = $tr['month ago'];
    }
    else{
        $time   = $diff_sec/31536000;
        //年前
        $unit   = $tr['year ago'];
    }

    return (int)$time .$unit;
}

// 判斷是瀏覽器還是手機
function get_device($https){
    global $tr;
    $the_text = '';
    $the_browser = '';
    $js = '';


    // 桌機
    if(strpos($https , 'Windows') == true){
        $the_text = $tr['browser'];
        $detail_href = <<<HTML
            <p class="show_https_detail">{$tr['show detail']}</p>
HTML;
    }else{
        $the_text = '行动版';
        $detail_href = '';
    }

    // 瀏覽器
    if($the_text == $tr['browser'] AND strpos($https,'Edge') == true){
        $the_browser = '(Internet Explorer)'; 
    }elseif($the_text == $tr['browser'] AND strpos($https,'Firefox') == true){
        $the_browser = '(Firefox)';
    }elseif($the_text == $tr['browser'] AND strpos($https,'Chrome') == true){
        $the_browser = '(Chrome)';
    }elseif($the_text == $tr['browser'] AND strpos($https,'UC Browser') == true){
        $the_browser = '(UC Browser)';
    }elseif($the_text == $tr['browser'] AND strpos($https,'Baidu') == true){
        $the_browser = '(Baidu)';
    }else{
        $the_browser = '';
    }
    if(isset($detail_href) AND $detail_href != NULL){
        $js=<<<HTML
            <script>
            $(document).ready(function() {
    
                $('.show_https_detail').unbind('click').on('click',function(e){
                    e.preventDefault();
                    var s_parent = $(this).closest('tr').attr('id'); // tr的id
    
                    if($('#dropdown_'+ s_parent).is(':hidden')){
                        $('#dropdown_'+ s_parent).slideDown("slow"); // 存取類型
                        $('#' + s_parent).find('.show_https_detail').text('{$tr['hide detail']}');

                    }else{
                        $('#dropdown_'+ s_parent).slideUp("slow"); // 存取類型
                        $('#' + s_parent).find('.show_https_detail').text('{$tr['show detail']}');
                    }
    
                })
    
            });
            </script>
HTML;
    }else{
        $js = '';
    }
    
    return $the_text.$the_browser.$detail_href.$js;
}

// 如果maxmind找不到IP地區資料
function ip_location_text($location){
    $text = '';
    if($location == '' OR $location == null){
        $text = '暂无地区资料';
    }else{
        $text = $location;
    }
    return $text;
}

?>