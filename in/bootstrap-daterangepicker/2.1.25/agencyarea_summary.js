// agencyarea_summary.js
$(function(){
    /* var currentdate = new Date(),
        datetime = currentdate.getFullYear() + '-' +
                    ( ((currentdate.getMonth()+1)<10) ? ('0'+(currentdate.getMonth()+1)) : (currentdate.getMonth()+1) ) + '-' +
                    ( (currentdate.getDate()<10) ? ('0'+currentdate.getDate()) : currentdate.getDate() );
    var seven_date_before = new Date();
    var nowMilliSeconds = currentdate.getTime();
    seven_date_before.setTime( nowMilliSeconds-(7*86400000) );
    seven_date_before_datetime = seven_date_before.getFullYear() + '-' +
                                ( ((seven_date_before.getMonth()+1)<10) ? ('0'+(seven_date_before.getMonth()+1)) : (seven_date_before.getMonth()+1) ) + '-' +
                                ( (seven_date_before.getDate()<10) ? ('0'+seven_date_before.getDate()) : seven_date_before.getDate() ); */

    // 初始化 DateRangePicker
    $("#DateRangePicker").daterangepicker({
        timePicker: false,
        locale: {
            format: "YYYY/MM/DD", // 日期+時間的顯示格式
            startDate: '',
            endDate: '',
            applyLabel : "確定",
            cancelLabel: "清除",
            fromLabel : "開始日期",
            toLabel : "結束日期",
            customRangeLabel : "自訂日期區間",
            daysOfWeek : [ "日", "一", "二", "三", "四", "五", "六" ],
            monthNames : [ "1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月" ],
            firstDay : 1,
            separator: " ~ "
        },
        showDropdowns: true, // 月份、年份有下拉選單可選擇
        ranges: {
            "今天": [moment(), moment()],
            "昨天": [moment().subtract(1, "days"), moment().subtract(1, "days")],
            "過去 7 天": [moment().subtract(6, "days"), moment()],
            "過去 30 天": [moment().subtract(29, "days"), moment()],
            "本月": [moment().startOf("month"), moment().endOf("month")],
            "上個月": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
        },
        alwaysShowCalendars: true // 總是顯示日曆
    }); // end daterangepicker

    // DateRangePicker 按下清除鈕
    $("body").on("cancel.daterangepicker", "#DateRangePicker", function(ev, picker){
        $(this).val("");
    });//end on


});//END FUNCTION