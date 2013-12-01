<?php
defined('PaZsCA8p') or exit;

/**
 * Class Template
 * @deprecated
 */
class Template {
    protected static $SiteHost = SITE_HOST;
    protected static $StaticHosts = array();
    protected static $mailFrom = 'Info <info@northamericanhuntingcompetition.com>';
    public static $Logo = '<img class="app_logo" src="images/spacer.gif" width="515" height="76" alt="UCI.edu" border="0" />';
    private static $cssAdds=array();
    private static $jsAdds=array();
    private static $headerComments = array();
    private static $pingsPerMinute=3;
    protected static $PageTitle = 'Test';
    private static $ajFMaxHeight=500;
    private static $TinyMce=false;
    private static $austere=false;
    private static $TM_upcss=false;//Use Website CSS inside TinyMce?
    private static $Ajax=false;
    private static $ScriptHead = true;
    private static $GoogleFonts=array();
    private static $Section = '';
    private static $FootAdditions = array();
    private static $NotificationsPlaceHolder = "\n<!-- Application Notifications -->\n";

    public static function addToFoot() {
        $args = func_get_args();
        foreach ($args as $arg) {self::$FootAdditions[] = $arg;}
        return true;
    }

    public static function scriptToFoot() {
        self::$ScriptHead = false;
    }

    public static function addCommentToHeader($comment) {
        self::$headerComments[] = $comment;
    }

    public static function addWrap(Request $Request, Response $Response) {
        $Response->set('content', self::getHeader($Request, $Response) . $Response->get('content') . self::getFooter($Request, $Response));
        self::addNotifications($R->content);
    }

    /*
    public static function ensureGoodJson(stdClass &$R) {
        if (is_array($R->content)) {
            foreach ($R->content as $k => $v) {
                if (is_object($v)) {
                    $R->content[$k] = strval($v);
                }
            }
        } elseif (is_string($R->content) && $R->exception) {
            $R->content = array('html' => $R->content);
        }
        if (isset($R->content['html']) && strpos($R->content['html'], '<script') !== false) {
            $js = preg_replace('#.*<script[^>]*>(.*?)</script>.*#s', '$1', $R->content['html']);
            if (!isset($R->content['js'])) $R->content['js'] = '';
            $R->content['js'] .= $js;
        }
        if (!empty(self::$DebugText)) {
            if (is_array($R->content)) {
                if (isset($R->content['html'])) {
                    $R->content['html'] .= '<pre>' . implode("\n", self::$DebugText) . '</pre>';
                }
            } else {
                $R->content .= '<pre>' . implode("\n", self::$DebugText) . '</pre>';
            }
        }
        $R->content = json_encode($R->content);
    }
    */

    public static function addNotifications($response) {
        if (!empty($_SESSION['msg']) || !empty($_SESSION['fmsg'])) {
            $msg = empty($_SESSION['msg']) ? '' : '<h2 class="msg s_msg">' . $_SESSION['msg'] . '</h2>';
            $msg .= empty($_SESSION['fmsg']) ? '' : '<h2 class="msg f_msg">' . $_SESSION['fmsg'] . '</h2>';
            $response = str_replace(self::$NotificationsPlaceHolder, $msg, $response);
            $_SESSION['msg'] = $_SESSION['fmsg'] = '';
        }
        return $response;
    }

    public static function setSection ($section) {
        return self::$Section = $section;
    }

    public static function setAdjective ($adj) {
        return self::setSection(Html::n('a', APP_SUB_DIR . '/' . ucfirst(strtolower(str_replace(' ', '_', $adj))) . '/', $adj));
    }

    public static function getSection ($section) {
        return self::$Section;
    }

    public static function Ajax(){
        self::$Ajax=!self::$Ajax;
    }

    public static function isAjax(){return self::$Ajax;}

    public static function Austere(){
        self::$austere=true;
        self::addCSS('austere');
        return true;
    }

    public static function UseTinyMce($upcss=false){
        self::$TinyMce=true;
        self::$TM_upcss=$upcss;
    }

    public static function getPingInt(){
        return floor(60000/self::$pingsPerMinute);
    }

    public static function getAjFMaxHeight(){
        return self::$ajFMaxHeight;
    }

    public static function appendTitle($str=''){
        self::$PageTitle.=' | '.trim(strip_tags($str));
        return true;
    }

    public static function setTitle($str=''){
        self::$PageTitle=trim(strip_tags($str));
        return true;
    }

    public static function addJS(){
        $js = func_get_args();
        if (count($js) < 2) $js = $js[0];
        if(is_array($js)){
            foreach($js as $j){self::addJS($j);}
            return true;
        }
        $js=trim(strip_tags($js));
        if($js!=''){
            self::$jsAdds[$js]=$js;
        }
        return true;
    }

    public static function addCSS(){
        $css = func_get_args();
        if (count($css) < 2) $css = $css[0];
        if(is_array($css)){
            foreach($css as $c){self::addCSS($c);}
            return true;
        }
        $css=trim(strip_tags($css));
        if($css!=''){
            self::$cssAdds[$css]=$css;
        }
        return true;
    }

    public static function addS(){
        $args = func_get_args();
        return self::addJS($args) && self::addCSS($args);
    }

    public static function cssOpacity($opac){
        if(self::isIE()){
            return 'filter:alpha(opacity='.($opac*100).');';
        }
        return 'opacity:'.$opac.';';
    }

    public static function isIE(){
        return preg_match('/msie/i',$_SERVER['HTTP_USER_AGENT']);
    }

    public static function isFireFox(){
        return preg_match('/firefox/i',$_SERVER['HTTP_USER_AGENT']);
    }

    public static function getFFVersion(){
        if(!self::isFireFox()){return 0;}
        $v=preg_replace('/^[\s\S]*?FireFox\/([\d\.]+)/','$1',$_SERVER['HTTP_USER_AGENT']);
        if(strpos($v,'.',1)!==false){
            $v=substr($v,0,strpos($v,'.',1));
        }
        $V=(float)$v;
        return $V;
    }

    public static function isSafari(){
        return (!preg_match('/chrome/i',$_SERVER['HTTP_USER_AGENT']) && preg_match('/safari/i',$_SERVER['HTTP_USER_AGENT']));
    }

    public static function isChrome(){
        return preg_match('/chrome/i',$_SERVER['HTTP_USER_AGENT']);
    }

    public static function isWebkit(){
        return preg_match('/webkit/i',$_SERVER['HTTP_USER_AGENT']);
    }

    public static function cssPrefix(){return self::getCSSPrefix();}

    public static function getCSSPrefix(){
        $p='';
        if(self::isFireFox() && self::getFFVersion()<4){
            $p='-moz-';
        }elseif(self::isWebkit() && !self::isChrome()){
            $p='-webkit-';
        }
        return $p;
    }

    public static function getTopNav() {
        $topNav = '';
        return $topNav;
    }

    private static function renderNav(){
        $uApp='';
        if(Visitor::uV()){
//			$uApp="\n\t".'<li><a href="/newProperty">Add a Property</a></li>';
        }
        $html=<<<EOT
<ul id="Nav">
	<li>Nav item 1</li>{$uApp}
</ul>
EOT;
        return $html;
    }

    public static function getStaticUrlPrefix($str='hi'){
        if (self::usingSSL() || empty(self::$StaticHosts)) return '';
        return 'http://'.self::$StaticHosts[preg_replace('/\D/','',md5($str))%count(self::$StaticHosts)].'/';
    }

    public static function getCSSAdd ($useUrlPrefix = true) {
        if (true || DEBUG) {
            $Css = array('style', 'app', 'fonts', 'colors');
            foreach (self::$cssAdds as $css) {
                $Css[] = $css;
            }
            $c = new HtmlC;
            foreach ($Css as $css) {
                Html::n('link', 't:text/css', '', $c)->rel("stylesheet")->href(APP_SUB_DIR . '/css/' . $css . '.css');
            }
            return $c;
        }
        $cssAdd = '';
        if (!empty(self::$cssAdds)) {
            $amp = '?';
            foreach (self::$cssAdds as $css) {
                $cssAdd .= $amp . $css;
                $amp = '&';
            }
        }
        return '<link rel="stylesheet" type="text/css" href="' . ($useUrlPrefix ? self::getStaticUrlPrefix('theCSS') : '') . 'css/suture.php' . $cssAdd . '" />';//styles.css
    }

    public static function getJSAdd ($async = false) {
        $jsAdd = '';
        if (!empty(self::$jsAdds)) {
            $amp = '?';
            foreach (self::$jsAdds as $js) {
                $jsAdd .= $amp . $js;
                $amp = '&';
            }
        }
        $s = Html::n('script', 't:text/javascript')->src(APP_SUB_DIR . '/js/suture.php' . $jsAdd);//script.js
        if ($async) {
            $s->async('true');
        }
        return $s;
    }

    public static function getTop(Request $R) {
        $top = new HtmlC;
        $bits = array();
        if (Visitor::UV()) {
            $bits[] = Html::n('a', APP_SUB_DIR . '/', 'Enter Another Competition');
        }
        $bits[] = Html::n('a', APP_SUB_DIR . '/OpenCompetitions', 'Open Competitions');
        $bits[] = Html::n('a', APP_SUB_DIR . '/ClosedCompetitions', 'Closed Competitions');
        if (Visitor::UV()) {
            $bits[] = Html::n('a', APP_SUB_DIR . '/MyCompetitions', 'My Competitions');
            $bits[] = Html::n('a', APP_SUB_DIR . '/UpdatePassword', 'Change Password');
        }
        if (Visitor::AV()) {
            array_unshift($bits, Html::n('a', APP_SUB_DIR . '/Admin', 'Admin Area'));
        }
        if (!empty($bits)) {
            $top->append(implode('&nbsp;|&nbsp;', $bits));
        }
        return $top->prepend(Html::n('div')->style("clear:both"));
    }

    public static function getBodyTag() {
        $classes = array();//'main_bg', '_26682');
        $class = '';
        if (!empty($classes)) {
            $class = ' class="' . implode(' ', $classes) . '"';
        }
//<body class="main_bg _26682" style="background-image:none;background-color:#666633">
        return '<body' . $class . '>';
    }

    public static function getHeader($invocation = array()) {
        $CSS = trim(self::getCSSAdd(false));
        $JS = self::$ScriptHead ? trim(self::getJSAdd()) : '';
        if (self::$TinyMce) {
            $JS .= Html::n('script', 't:text/javascript', '')->src(APP_SUB_DIR . '/js/tinyMCE/jquery.tinymce.js');
            $JS .= Html::n('script', 't:text/javascript', '')->src(APP_SUB_DIR . '/js/tinyMCE/tiny_mce.js');
            $JS .= Html::n('script', 't:text/javascript', <<<EOT
(function ($) {
	$(function () {
		$('.tinymce').tinymce({
			theme : 'advanced',
			width : 735,
			height : 450,
			theme_advanced_buttons3 : null,
		});
	});
}(jQuery));
EOT
            );
        }
        $pageTitle=self::$PageTitle;
        $logo=self::$Logo;
        $bodyTag = self::getBodyTag();
        $BaseUrl=self::getStaticUrlPrefix('baseUrl');
        $GF='';
        if(!empty(self::$GoogleFonts)){
            $fs=$comma='';
            foreach(self::$GoogleFonts as $font){
                $fs.=$comma."'".$font."'";
                $comma=', ';
            }
            $GF=<<<EOT
<script type="text/javascript">
  WebFontConfig = {
    google: { families: [ {$fs} ] }
  };
  (function() {
    var wf = document.createElement('script');
    wf.src = ('https:' == document.location.protocol ? 'https' : 'http') +
        '://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
    wf.type = 'text/javascript';
    wf.async = 'true';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(wf, s);
  })();
</script>

EOT;
        }
        $base_href = 'http' . (self::usingSSL() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . APP_SUB_DIR . '/';
        $headerComments = '';
        foreach (self::$headerComments as $Comm) {
            $headerComments .= "\n<!--\n{$Comm}\n-->";
        }

        //<meta name="description" content="Shad is a Web Developer living in Portland, Oregon" />

        $html=<<<EOT
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="robots" content="ALL" />
<base href="{$base_href}" />
<link rel="shortcut icon" href="favicon.ico" />
{$CSS}
{$JS}
{$GF}<title>{$pageTitle}</title>
</head>
{$bodyTag}
EOT;
        if (self::$austere) {
            return $html . '<table id="austere" border="0" cellspacing="0" cellpadding="0" align="center"><tr><td id="content">';
        }
        $notifications = self::$NotificationsPlaceHolder;
        $logo = self::$Logo;
        $AdminNav = self::getAdminNav($invocation);
        $html .= <<<EOT
<div id="wrapper">
	<div id="top_wrap" class="wrapper">
		<a href="/">{$logo}</a>
		<h1><a href="">Colorectal Risk Assessment</a></h1>
	</div>
	{$AdminNav}
	<div id="mid_wrap" class="wrapper">{$notifications}
EOT;
        return $html;
    }

    public static function getFooter(){
        $JS = '';
        foreach (self::$FootAdditions as $add) {
            $JS .= $add;
        }
        $JS .= self::$ScriptHead ? '' : trim(self::getJSAdd(true));
        if(self::$austere){return '</td></tr></table>' . $JS . '</body></html>';}
        $html=<<<EOT
	</div>
	<div id="bot_wrap" class="wrapper">
		<p>
			University of California, Irvine - Irvine, CA 92697 : 949-824-5011<br>
			<a href="/comments.php">Comments &amp; Questions</a> | <a href="/privacy.php">Privacy &amp; Legal Notice</a> | <a href="/copyright.php">Copyright Inquiries</a> | &copy; 2012 UC Regents
		</p>
	</div>
</div>
{$JS}
</body>
</html>
EOT;
        return $html;
    }

    public static function getAdminNav ($invocation = array()) {
        $nav = new HtmlC;
        $U = UserFactory::build();
        if (is_a($U, 'Admin')) {
            Html::n('div', 'i:admin_nav;align:center', $U->renderNavItems($invocation), $nav);
        }
        return $nav;
    }

    public static function getPage($rp=NULL){
        if(is_null($rp)){$rp='homepage';}
        $rp=preg_replace('/(\.\.\/?)+/','',$rp);
        $rp=preg_replace('/\//','',$rp);
        $rphtm=$rp.'.html';
        if(!file_exists(SITE_ROOT.$rphtm)){
            return self::NotFound();
        }
        $html=file_get_contents(SITE_ROOT.$rphtm);
        return $html;
    }

    public static function usingSSL(){
        return (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']==443);
    }

    public static function ForceSSL(){
        if($_SERVER['SERVER_PORT']!=443){
            header('location: https://' . SITE_HOST . $_SERVER['REQUEST_URI']);
            exit;
        }
        return true;
    }

    public static function view_NotFound() {
        return self::NotFound();
    }

    public static function do_NotFound() {
        return self::NotFound();
    }

    public static function NotFound ($bounce = false, $incHeader = true) {
        if ($incHeader) header('Not Found', true, 404);
        if ($bounce) UtilsHttp::bounceTo(APP_SUB_DIR . '/');
        Template::setAdjective('Confused');
        return '<h1>We\'re sorry, the page you requested cannot be found</h1>';
    }

    public static function checkToDo ($post=array()) {
        $s='tStamp='.time().';';
        $post['t']=isset($post['t'])?$post['t']:0;
        $t=abs((int)$post['t']);
        return '<script type="text/javascript">'.$s.'</script>';
    }

    public static function view_HomePage(Request $Request, Response $Response){
        $html = new HtmlC;
        Html::n('h1', '', 'HomePage', $html);
        $Response->content = $html;
    }

}

