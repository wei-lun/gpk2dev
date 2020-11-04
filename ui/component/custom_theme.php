<?php
//客製化theme
//桌機板
function template_themes($template) {
	global $cdnfullurl;
	global $cdnfullurl_js;
	global $tmpl;
	global $ui_data;
	global $config;
	if($template=='desktop'){		
		if(isset($ui_data["template_themes"]["switch"]) AND $ui_data["template_themes"]["switch"]==1){
				$colorlist=[
					substr($ui_data["template_themes"]["main_color"],1),
					substr($ui_data["template_themes"]["sub_color"],1),
					substr($ui_data["template_themes"]["font_color"],1)
				];

				$tmpl['extend_head'] .=<<<HTML
			<style data-cssvarsponyfill="true">
			:root {
			    --main: #{$colorlist[0]};
			    --sub:#{$colorlist[1]};
			    --font: #{$colorlist[2]};
			}
			</style>
			<link data-cssvarsponyfill="true" type="text/css" rel="stylesheet" href="{$cdnfullurl}css/custom.css?version_key={$config['cdn_version_key']}">
			<script src='{$cdnfullurl_js}js/css-vars-ponyfill.min.js'></script>
			<script type="text/javascript">
			  cssVars({
			    include: '[data-cssvarsponyfill="true"]'
			  });
			</script>
HTML;

		}
	}
	else{
		if(isset($ui_data["template_themes_m"]["switch"]) AND $ui_data["template_themes_m"]["switch"]==1){
			$colorlist_m=[
				substr($ui_data["template_themes_m"]["main_color"],1),
				substr($ui_data["template_themes_m"]["sub_color"],1),
				substr($ui_data["template_themes_m"]["font_color"],1)
			];

			$tmpl['extend_head'].=<<<HTML
		<style data-cssvarsponyfill="true">
		:root {
		    --main: #{$colorlist_m[0]};
		    --sub:#{$colorlist_m[1]};
		    --font: #{$colorlist_m[2]};
		}
		</style>
		<link data-cssvarsponyfill="true" type="text/css" rel="stylesheet" href="{$cdnfullurl}css/custom.css?version_key={$config['cdn_version_key']}">
		<script src='{$cdnfullurl_js}js/css-vars-ponyfill.min.js'></script>
		<script type="text/javascript">
		  cssVars({
		    include: '[data-cssvarsponyfill="true"]'
		  });
		</script>
HTML;

		}
	}
}

//手機版
/*
if(isset($tmpl['extend_head'])){
	if(isset($ui_data["template_themes_m"]["switch"]) AND $ui_data["template_themes_m"]["switch"]==1){
		$colorlist_m=[
			substr($ui_data["template_themes_m"]["main_color"],1),
			substr($ui_data["template_themes_m"]["sub_color"],1),
			substr($ui_data["template_themes_m"]["font_color"],1)
		];

		$tmpl['extend_js'].=<<<HTML
	<style data-cssvarsponyfill="true">
	:root {
	    --main: #{$colorlist_m[0]};
	    --sub:#{$colorlist_m[1]};
	    --font: #{$colorlist_m[2]};
	}
	</style>
	<link data-cssvarsponyfill="true" type="text/css" rel="stylesheet" href="{$cdnfullurl}css/custom.css?version_key={$config['cdn_version_key']}">
	<script src='{$cdnfullurl_js}js/css-vars-ponyfill.min.js'></script>
	<script type="text/javascript">
	  cssVars({
	    include: '[data-cssvarsponyfill="true"]'
	  });
	</script>
HTML;

	}
}else{
//桌機板
	if(isset($ui_data["template_themes"]["switch"]) AND $ui_data["template_themes"]["switch"]==1){
		$colorlist=[
			substr($ui_data["template_themes"]["main_color"],1),
			substr($ui_data["template_themes"]["sub_color"],1),
			substr($ui_data["template_themes"]["font_color"],1)
		];

		$ui['extend_head'] .=<<<HTML
	<style data-cssvarsponyfill="true">
	:root {
	    --main: #{$colorlist[0]};
	    --sub:#{$colorlist[1]};
	    --font: #{$colorlist[2]};
	}
	</style>
	<link data-cssvarsponyfill="true" type="text/css" rel="stylesheet" href="{$cdnfullurl}css/custom.css?version_key={$config['cdn_version_key']}">
	<script src='{$cdnfullurl_js}js/css-vars-ponyfill.min.js'></script>
	<script type="text/javascript">
	  cssVars({
	    include: '[data-cssvarsponyfill="true"]'
	  });
	</script>
HTML;

	}
}
*/
?>