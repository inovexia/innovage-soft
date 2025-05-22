jQuery(document).ready(function(){
	let universal_html = `<style>#siteseo-universal-metabox-icon{display:inline-flex; justify-content:center; align-items:center; position: fixed;bottom: 10px;left: 10px;z-index: 100000; background-color: #003399; padding: 6px; border-radius: 50%; cursor:pointer; width:40px height:40px;} #siteseo-universal-metabox-icon img{width:36px; height:36px;}
	.siteseo-universal-metabox{
		position: fixed;
		left: 0px;
		bottom: 0px;
		z-index: 100100;
		background-color: rgb(255, 255, 255);
		width: 100%;
		max-width: 100%;
		min-width: 100%;
		height: 400px;
		box-sizing: border-box;
		flex-shrink: 0;
		border: none;
		max-height: calc(100% - 93px);
		text-transform: none;
		display: none;
		box-sizing:border-box;
	}
	.siteseo-universal-metabox-header{
		display:flex;
		justify-content:space-between;
		border-bottom: 0px;
		border-top: 1px solid rgb(221, 221, 221);
		border-bottom: 1px solid rgb(221, 221, 221);
		padding:10px 24px;
	}
	.siteseo-universal-metabox-header h2{
		margin:0;
		font-size:16px;
	}
	.siteseo-universal-metabox-body{
		height:calc(100% - 40px);
	}
	.siteseo-universal-spinner{display:none;position:absolute; top: 30%; left: 50%; transform: translateX(-50%); border-radius:50%;animation: siteseo-universal-spinner 1s linear infinite;height: 2rem;width: 2rem;border: 4px solid #dddcdc80;border-left-color: #e3e3e3;} @keyframes siteseo-universal-spinner{ 0% { transform: rotate(0deg);} 100% {transform: rotate(360deg);}}
	</style>
	<div class="siteseo-universal-modal"><div class="siteseo-universal-metabox"><div class="siteseo-universal-metabox-header"><h2>SiteSEO</h2><span class="dashicons dashicons-no-alt" onclick="siteseo_close_universal()" style="cursor:pointer;"></span></div><div class="siteseo-universal-spinner"></div><div class="siteseo-universal-metabox-body"><iframe onload="siteseo_onload_universal_iframe(event)" data-src="${siteseo_universal.metabox_url}&post=${siteseo_universal.post_id}" style="width:100%;height:100%;border:0;display:none"/></iframe></div></div><div id="siteseo-universal-metabox-icon" onclick="siteseo_toggle_universal_modal()"><img src="${siteseo_universal.asset_url}/img/logo-24.svg"></div></div>`;
	
	jQuery('body').append(universal_html);

});

function siteseo_toggle_universal_modal(){
	let modal = jQuery('.siteseo-universal-metabox'),
	iframe = modal.find('iframe'),
	src_val = iframe.data('src'),
	spinner = modal.find('.siteseo-universal-spinner'),
	src = iframe.attr('src');
	modal.show();

	if(src){
		return;
	}

	spinner.show();
	iframe.attr('src', src_val);
}

function siteseo_onload_universal_iframe(e){
	jQuery('.siteseo-universal-spinner').hide();
	jQuery(e.target).show();
}

function siteseo_close_universal(){
	jQuery('.siteseo-universal-metabox').hide();
	
}