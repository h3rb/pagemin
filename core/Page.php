<?php

/*********************************************************************************************
 *  __    __________________   ________________________________   __________  ________       *
 * /\ \  /\  __ \  ___\__  _\ /\  __ \  ___\__  _\  == \  __ \ "-.\ \  __ \ \/\ \__  _\ (tm) *
 * \ \ \_\_\ \/\ \___  \/\ \/ \ \  __ \___  \/\ \/\  __<\ \/\ \ \-.  \  __ \ \_\ \/\ \/      *
 *  \ \_____\_____\_____\ \_\  \ \_\ \_\_____\ \_\ \_\ \_\_____\_\\"\_\_\ \_\_____\ \_\      *
 *   \/_____/_____/_____/\/_/   \/_/\/_/_____/\/_/\/_/\/_/_____/_/ \/_/_/\/_/_____/\/_/      *
 *    --------------------------------------------------------------------------------       *
 *     Page Framework (c) 2007-2016 H. Elwood Gilliland III - MIT Licensed Open Source       *
 *********************************************************************************************/

// global $plog_level; $plog_level=1;

 include_once 'path.php'; //<- change siteroot here to move site
 include_once 'errors.php';
 include_once 'utility.php';
 include_once 'unique.php';
 include_once 'engines.php';
 include_once 'root.php';
 include_once 'Database.php';
 include_once 'ui.php';

 // Basic (minimal) bootstrapping.
 include_once SITE_ROOT.'/settings/config.php';
 include_once SITE_ROOT.'/settings/config.flags.php';
 include_once SITE_ROOT.'/settings/config.enums.php';
 include_once SITE_ROOT.'/settings/config.global.php';
 include_once SITE_ROOT.'/settings/config.databases.php';
 include_all(SITE_ROOT.'/model/');
 include_all(SITE_ROOT.'/global/');
 include_all(SITE_ROOT.'/shreds/');
 include_all(SITE_ROOT.'/ui/');
 include_once 'Auth.php';
 // We're done!

 global $_bound;
 $_bound=0;
 class Header extends Root {
  var $string, $replace, $http_response_code;
  public function construct( $string, $replace=true, $http_response_code=200 ) {
   $this->string=$string;
   $this->replace=$replace;
   $this->http_response_code=$http_response_code;
  }
  public function Execute() {
   plog( 'header sent :'.$string );
   header( $string, $replace, $http_response_code );
  }
 };

 // All javascript source documents must exist in the folder siteroot/js/
 class Javascript extends Root {
  var $js;
  // $js may be either a string or an array, where the array is an array of
  // stylesheet names found under the js/ folder, or if it is a string it is
  // either a file (javascript) or inline SCRIPT tag content
  public function __construct( $js ) {
   if ( is_array($js) ) $this->js=$js;
   else if ( is_string($js) ) $this->js=$js;
   else error( 'Javascript:construct(`'.$js.'`)','parameter was not a string or array');
  }
  public function Source() {
   if ( is_string($this->js) && strlen($this->js) == 0 ) return '';
   if ( is_array($this->js) ) {
    $out="";
    foreach ( $this->js as $jsfile )
     if ( isfile($this->js,'js/') === TRUE )
      $out.='<SCRIPT src="'.'js/'.$jsfile.'" type="text/javascript"></SCRIPT>';
   } else {
    if ( is_string($this->js) ) {
     $words=count(words($this->js));
     if ( $words == 1 && contains($this->js,"//cdn" ) )
      return '<SCRIPT src="'.$this->js.'" type="text/javascript"></SCRIPT>';
     else if ( $words == 1 && contains($this->js,"http") && !contains($this->js,"\n") )
      return '<SCRIPT src="'.$this->js.'" type="text/javascript"></SCRIPT>';
     else if ( isfile($this->js,'js/',FALSE) )
      return '<SCRIPT src="'.'js/'.$this->js.'" type="text/javascript"></SCRIPT>';
     else if ( ($words == 1
        && !contains($this->js,"\n")
        && !contains($this->js,'"')
        && !contains($this->js,'$')
        && !contains($this->js,';')) )
      return '<SCRIPT type="text/javascript">'.$this->js.'</SCRIPT>';
     else // Assume it is an inline script tag
      return '<SCRIPT type="text/javascript">'.$this->js.'</SCRIPT>';
    } else {
     error("Javascript:Source()","Style input was set to non-array, non-string which is not supported");
     return "";
    }
   }
  }
 };


 // All CSS stylesheets must exist in the folder siteroot/css/
 class Stylesheet extends Root {
  var $style;
  // $style may be either a string or an array, where the array is an array of
  // stylesheet names found under the css/ folder, or if it is a string it is
  // either a file (stylesheet) or inline STYLE tag content
  public function __construct( $style ) {
   if ( is_array($style) ) $this->style=$style;
   else if ( is_string($style) ) $this->style=$style;
   else error('Stylesheet:construct(`'.$style.'`)','parameter was not an array or string');
  }
  public function Get($stylesheet) {
   $words=count(words($stylesheet));
   if ( stripos(trim($stylesheet),"//cdn.") ===0 )
   return '<LINK href="'.$stylesheet.'" rel="stylesheet" type="text/css">';
   if ( isfile($stylesheet,'css/',FALSE) === TRUE )
   return '<LINK href="'.'css/'.$stylesheet.'" rel="stylesheet" type="text/css">';
   else if ( ($words == 1 && !contains($stylesheet,"\n") && !contains($stylesheet,';'))
        || stripos(trim($stylesheet),"http")===0 )
   return '<LINK href="'.$stylesheet.'" rel="stylesheet" type="text/css">';
   else // Assume it is an inline style tag
   return '<STYLE type="text/css">'.$stylesheet.'</STYLE>';
  }
  public function Source() {
   if ( is_array($this->style) ) {
    $out="";
    foreach ( $this->style as $stylesheet ) $out.=$this->Get($stylesheet);
   } else $out=$this->Get($this->style);
   return $out;
  }
 };

 class View extends Root {
  var $name,$file,$fragment;
  public function __construct( $file ) {
   $this->file = $file;
   $this->fragment = array();
   $this->name = "";
   Load();
  }

  public function Load() {
   if ( is_array($this->file) ) {
    foreach ( $this->file as $file )
     if ( isfile($file,'view/') )
      $this->fragment[]=file_get_contents('view/'.$file);
   } else if ( is_string($this->file) ) {
    if ( isfile($this->file,'view/',FALSE) ) $this->fragment[]=file_get_contents($this->file);
    else $this->fragment[]=$this->file;
   } else error('View(`'.$s.'`)','Invalid type not string or array provided as filename');
  }

  public function Recurse( $page ) {
   foreach ( $this->fragment as &$fragment ) {
    $result=eval($fragment);
    if ( $result === NULL ) $fragment='';
    else $fragment=$result;
   }
  }

  public function Source() {
   $out="";
   foreach ( $this->fragment as $fragment ) $out.=$fragment;
   return $out;
  }
 };

 class KV {
  var $values;
  public function __construct() {
   $this->values=array();
  }
  public function Load( $name, $file ) {
   if ( isfile($file) === TRUE )
   $this->values[$name]=file_get_contents($file);
   else $this->values[$name]=NULL;
  }
  public function Set( $name, $value ) {
   $this->values[$name]=$value;
  }
  public function Get( $name, $value ) {
   return isset( $this->values[$name] ) ? $this->values[$name] : '';
  }
  public function Execute($name) {
   return eval(Get($name));
  }
 };

 class KVStack {
  var $values,$count;
  public function __construct() {
   $this->values=array();
   $this->count=0;
  }
  public function Load( $file ) {
   if ( isfile($file) === TRUE ) return Push(file_get_contents($file));
  }
  public function Push( $data ) {
   $this->values[]=$data;
   $this->count=count($data);
   return $this->count-1;
  }
  public function Pop() {
   $out=array_pop($this->values);
   $this->count=count($this->values);
   return $out;
  }
  public function Shift() {
   $out=array_shift($this->values);
   $this->count=count($this->values);
   Renumber();
   return $out;
  }
  public function Prepend( $data ) {
   $this->count=array_unshift($this->values,$data);
   Renumber();
  }
  public function Renumber() {
   $replace=array();
   foreach ( $this->values as $value ) $replace[]=$value;
   $this->values=$replace;
  }
 };

 class Page {

  var $name,$title,$viewport,$eol_source,$eol_html,$doctype,$header,$head,$body,$view,$kv,$stack,$ui,$jq,$jq_loaded,$angular_loaded;
  var $ua,$ajax;

  // $doctype parameter provides a way to override default HTML5 style document.
  // If $doctype is NULL, FALSE or an empty string "" it will not display, this is
  // used for special header cases where a DOCTYPE is not required, such as file
  // download pages and AJAX responses.
  public function __construct( $name="", $doctype='<!DOCTYPE html>' ) {
   $this->name=$name;
   $this->title=sitename;
   $this->eol_source="\n";
   $this->eol_html='<BR>';
   $this->html_start='<HTML>';
   $this->html_head_start='<HEAD>';
   $this->html_body_start='<BODY>';
   $this->header=array();
   $this->head=array();
   $this->body=array();
   $this->doctype=$doctype;
   $this->kv=new KV();
   $this->stack=new KVStack();
   $this->ui=array();
   $this->viewport="device-width";
   $this->jq=array();
   $this->jq_loaded=FALSE;
   $this->angular_loaded=FALSE;
   $this->ajax=Page::isAJAXed();
  }

  static public function isAJAXed() {
   return ( isset($_POST['ajax']) || isset($_GET['ajax']) ) ? TRUE : FALSE;
  }

  public function Name($name) {
   $this->name=$name;
  }

  public function is_Named($name) {
   return matches($name,$this->name);
  }

  public function Header( $string, $replace=true, $http_response_code=200 ) {
   $this->header[]=$returned=(new Header( $string, $replace, $http_response_code ));
   return $returned;
  }

  public function CSS( $as, $body=FALSE ) {
   return $this->Style($as,$body);
  }
  public function Style( $as, $body=FALSE ) {
   if ( $body !== FALSE ) $this->body[]=($returned=new Stylesheet( $as ));
   else $this->head[]=($returned=new Stylesheet( $as ));
   return $returned;
  }

  public function Font( $family ) {
   return '<link href="http://fonts.googleapis.com/css?'.$family.'" rel="stylesheet" type="text/css">';
  }

  public function Fragment( $id, $uri, $params=NULL ) {
   global $ajax_unique;
   $ajax_unique++;
   if ( is_null($params) ) $params=array( "ajax"=>$ajax_unique, "u"=>$ajax_unique );
   else { $params['ajax']=$ajax_unique; $params['u']=$ajax_unique; }
   $code=PHP_EOL.'$.ajax({
  context:document, type:"POST", dataType:"html",
      url:"'.$uri.'",
     data: "'.ajaxvars($params,'&').'"
  }).done(function(r) { $("#'.$id.'").html(r); });'.PHP_EOL;
   $this->HTML('<div id="'.$id.'_fragment_invis" style="display:none"></div>'.$this->eol_source);
   return $this->Javascript($code,TRUE);
  }

  public function JS( $as, $body=FALSE ) {
   return $this->Javascript($as,$body);
  }
  public function Javascript( $as, $body=FALSE ) {
   if ( $body !== FALSE ) $this->body[]=($returned=new Javascript( $as ));
   else $this->head[]=($returned=new Javascript( $as ));
   return $returned;
  }

  // Adds to the jquery doc ready, loads jquery if not loaded
  public function JQ( $s ) {
   $this->Jquery();
   $this->jq[]=$s;
  }

  public function View( $as, $recurse=TRUE ) {
   if ( is_string($as) ) {
    $view=new View($as);
    if ( $recurse === TRUE ) $view->Recurse($this);
    $this->view[]=$view;
    return $view;
   } else if ( is_array($as) ) {
    $result=array();
    foreach ( $as as $a ) {
     $result[]=$this->View($a,$recurse);
    }
    return $result;
   } else error('Page:View(`'.$as.'`)','parameter was not a string or array');
  }
  /*
  public function pHTML( $phtml ) {
   if ( is_array($phtml) ) { // Load and append
   } else {
    $file=file_get_contents('phtml/'.$phtml);
    // extract the PHP
    $file.='<?php ?>'.$phtml;
    $result=eval($phtml);
   }
  }
  */
  public function HTML( $html, $replacements=FALSE, $body=TRUE ) {
   if ( isfile($html,'html/',FALSE) ) $html=file_get_contents('html/'.$html);
   $out='';
   if ( false_or_null($replacements)===TRUE ) {
    if ( is_array($html) ) foreach( $html as $h ) $out.=$h;
    else $out=$html;
   } else {
    if ( is_array($replacements) ) {
     if ( is_array($html) ) {
      foreach($html as $h) {
       if ( isfile($h,'html/',FALSE) )
       $replaced=file_get_contents('html/'.$h);
       else $replaced=$h;
       foreach ( $replacements as $string=>$replace ) $replaced=str_replace($string,$replace,$replaced);
       $out.=$replaced;
      }
     } else {
      if ( isfile($html,'html/',FALSE) ) $replaced=file_get_contents($html);
      else $replaced=$html;
      foreach ( $replacements as $string=>$replace ) $replaced=str_replace($string,$replace,$replaced);
      $out=$replaced;
     }
    } else error('Page:HTML(`'.$html.'`,`'.$replacements.'`','replacements provided was not array');
   }
   if ( $body === TRUE ) $this->body[]=$out;
   else $this->head[]=$out;
   return $out;
  }

  public function Source( $ajax = FALSE ) {
   // Figure out the UI contribution
   $ui_head_js="";
   $ui_body_js="";
   $ui_css="";
   $ui_js_data="";
   foreach ( $this->ui as $ui ) {
    $ui->_Implement();
    if ( count($ui->js_data)  > 0 ) $ui_js_data.=$ui->_GetPreloaded();
    if ( strlen($ui->head_js) > 0 ) $ui_head_js.=$ui->head_js;
    if ( strlen($ui->body_js) > 0 ) $ui_body_js.=$ui->body_js;
    if ( strlen($ui->css) > 0 ) $ui_css.=$ui->css;
   }
   // Generate the page source
   if ( !$this->ajax ) {
    $out=$this->doctype . $this->eol_source;
    $out.=$this->html_start . $this->eol_source;
    $out.=$this->html_head_start . $this->eol_source;
    $out.='<meta name="viewport" content="width='.$this->viewport.'">'.$this->eol_source;
    $out.='<TITLE>'.$this->title.'</TITLE>' . $this->eol_source;
    foreach ( $this->head as $head ) {
     $out.=is_string($head) ? $head : $head->Source() . $this->eol_source;
    }
    if ( strlen($ui_css) > 0 ) {
     $out.='<STYLE type="text/css">' . $this->eol_source
         . $ui_css . $this->eol_source
         . '</STYLE>' . $this->eol_source;
    }
   }
   if ( strlen($ui_js_data) > 0 ) {
    $out.='<SCRIPT type="text/javascript">' . $this->eol_source
          . $ui_js_data . $this->eol_source
          .'</SCRIPT>' . $this->eol_source;
   }
   if ( strlen($ui_head_js) > 0 ) {
    $out.='<SCRIPT type="text/javascript">' . $this->eol_source
          . $ui_head_js . $this->eol_source
          .'</SCRIPT>' . $this->eol_source;
   }
   if ( count($this->jq) > 0 ) {
    $doc_ready=implode($this->eol_source,$this->jq);
    if ( !$this->ajax ) {
     $out.='<SCRIPT type="text/javascript">'.$this->eol_source
         .'jQuery(document).ready(function(){/////'.$this->eol_source.$this->eol_source
         .$doc_ready.$this->eol_source.'});/////'.$this->eol_source.'</SCRIPT>'.$this->eol_source;
    } else $out.='<SCRIPT type="text/javascript">'.$this->eol_source.$doc_ready.$this->eol_source.'</SCRIPT>';
   }
   if ( !$this->ajax ) {
    $out.='</HEAD>' . $this->eol_source;
    $out.=$this->html_body_start . $this->eol_source;
   }
   if ( strlen($ui_body_js) > 0 ) {
    $out.='<SCRIPT type="text/javascript">' . $this->eol_source
          . $ui_body_js . $this->eol_source
          .'</SCRIPT>' . $this->eol_source;
   }
   foreach ( $this->body as $body ) {
    $out.=is_string($body) ? $body : $body->Source() . $this->eol_source;
   }
   if ( !$this->ajax ) {
    $out.='</BODY>' . $this->eol_source;
    $out.='</HTML>';
   }
   return $out;
  }

  public function AJAX() {
   print $this->Source(TRUE);
  }

  public function Render( $send_headers=TRUE ) {
   if ( $send_headers === TRUE && !headers_sent() )
    foreach ( $this->header as $header ) $header->Execute();
   print $this->Source();
  }

  static public function Redirect( $uri=FALSE, $force_js=FALSE ) {
   if ( $force_js === FALSE && Page::isAJAXed() ) $force_js=true;
   if ( $uri === FALSE ) $uri = $_SERVER['HTTP_REFERER'];
   plog( "page->Redirect: ".$uri );
   if ( headers_sent() || $force_js !== FALSE ) { echo redirect($uri); die; }
   else { header("Location: $uri"); die; }
  }

  public function Bootstrap() {
   $this->JS( CDN_BOOTSTRAP_JS );
   $this->CSS( CDN_BOOTSTRAP_CSS );
   $this->CSS( CDN_BOOTSTRAP_THEME );
  }

  public function Jquery( $ui=TRUE ) {
   if ( $this->jq_loaded === FALSE ) {
    $this->JS( CDN_JQUERY_LATEST );
    if ( $ui === TRUE ) {
     $this->JS( CDN_JQUERY_UI );
//     $this->CSS( CDN_JQUERY_UI_CSS );
//     $this->JS( 'jquery-ui.min.js' );
     $this->CSS( 'css/jquery-ui.css' );
     $this->CSS( 'css/jquery-ui.theme.css' );
    }
    $this->jq_loaded=TRUE;
   }
  }
  
  public function Angular( $suppress_ngapp_in_html=FALSE ) {
   if ( $this->angular_loaded === FALSE ) {
    $this->JS( CDN_ANGULAR_LATEST );
    $this->angular_loaded=TRUE;
    if ( $suppress_ngapp_in_html !== FALSE ) {
     $this->html_start='<HTML ng-app>';
    }
   }
  }  

  public function isDesktop() {
   return $this->isMobile() ? FALSE : TRUE;
  }

  public function isMobile() {
   $this->ua = new uagent_info;
   return ($this->ua->isTierTablet
        || $this->ua->isTierIphone
        || $this->ua->isMobilePhone
        || $this->ui->isAndroidPhone
        || $this->ua->isTierGenericMobile) ? TRUE : FALSE;
  }

  public function Viewport( $width="device-width" ) {
   $this->viewport=$width;
  }

  public function Anchor( $name ) {
   $this->HTML( '<a name="'.$name.'"></a>' );
  }

  public function Add( $o, $replacements=FALSE ) {
   if ( is_object($o) ) {
    if ( get_class($o) == 'DataForm' ) {
     $o->form->Prepare();
     $this->JQ($o->form->jq,TRUE);
     $this->JS($o->form->js);
     $this->HTML($o->form->html,$replacements);
    }
   } else {
   }
  }

  public function Table( $tablehelper ) {
   $tablehelper->Render($table);
   $this->HTML($table);
  }

 };
