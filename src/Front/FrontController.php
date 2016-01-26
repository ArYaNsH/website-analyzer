<?php

namespace Front;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use \DOMdocument;
use \DOMXpath;


class FrontController {

    public function __construct() { }

    public function homepage(Application $app, Request $request) {
        
        $data = array(
            'url' => 'your url',);
        $form = $app['form.factory'] -> createBuilder('form')
            ->add('url', 'url', array('label' => false, 'attr' => array('placeholder' => 'Insert your URL', 'class' => 'form-control')))
            ->add('analyze', 'submit', array('label' => 'Analyze', 'attr' => array('class' => 'btn btn-default')))
            ->getForm();
        $form->handleRequest($request);
        if ($request->getMethod() == 'POST') {
            $date = date("Y-m-d h:i:sa");

            $data = $form->getData();
            $target = $data['url'];

            //$target = "http://www.ymc.ch/en/webscraping-in-php-with-guzzle-http-and-symfony-domcrawler";
            $page = new HttpCurl();
            $page->get($target);
            /*echo " Web Page Header<br>";
            print_r($page->getHeader());
            echo "<br>";
            echo " Web Page Status<br>";
            print_r($page->getStatus());
            echo "<br>";
            echo " Web Page Body<br>";*/
            $htmlContent = $page->getBody();

            //function to convert relative url to absolute
            function rel2abs( $rel, $base ) {

            // parse base URL  and convert to local variables: $scheme, $host,  $path
            extract( parse_url( $base ) );

            if ( strpos( $rel,"//" ) === 0 ) {
                return $scheme . ':' . $rel;
            }

            // return if already absolute URL
            if ( parse_url( $rel, PHP_URL_SCHEME ) != '' ) {
                return $rel;
            }

            // queries and anchors
            if ( $rel[0] == '#' || $rel[0] == '?' ) {
                return $base . $rel;
            }

            // remove non-directory element from path
            $path = preg_replace( '#/[^/]*$#', '', $path );

            // destroy path if relative url points to root
            if ( $rel[0] ==  '/' ) {
                $path = '';
            }

            // dirty absolute URL
            $abs = $host . $path . "/" . $rel;

            // replace '//' or  '/./' or '/foo/../' with '/'
            $abs = preg_replace( "/(\/\.?\/)/", "/", $abs );
            $abs = preg_replace( "/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs );

            // absolute URL is ready!
            return $scheme . '://' . $abs;
            }

            //exploding contents of web page
            $dom = new DOMdocument;
            @$dom->loadHTML($htmlContent);
            $dom->preserveWhiteSpace = false;
            $xpath = new DOMXpath($dom);
            //define which selector to extract
            $selector = '//img';
            $tags = $xpath->query($selector);
            //print_r($tags);
            $tagsLength = $tags->length;
            # loop over all <img> tags and convert them to data uri
            for ($i = 0; $i < $tagsLength; $i ++){
                $tag = $tags->item($i);
                $src = $tag->getAttribute('src');
                $images[] = rel2abs($src, $target);
            }



            //------------------capture image-----------------------//
            foreach ($images as $key) {
                $url = $key;
                $path = realpath(__DIR__ . '/../../');
                $img = $path . '/files/DownloadedImages/' . basename($url);
                file_put_contents($img, file_get_contents($url));
            }
            

            //------------------capture css file-----------------------//
            

            //------------------capture js file-----------------------//
            

            //------------------capture all html files-----------------------//
            $regexp = '<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>';
            if (preg_match_all("/$regexp/siU", $htmlContent, $matches, PREG_SET_ORDER)) {
                foreach($matches as $key) {
                    $links[] = ($key[2]);
                }
                
            }

            foreach ($links as $key => $value) {
            // Create a URL handle.
            $ch = curl_init();

            // Tell curl what URL we want.
            curl_setopt($ch, CURLOPT_URL, $value);

            // We want to return the web page from curl_exec, not 
            // print it.
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

            // Set this if you don't want the content header.
            curl_setopt($ch, CURLOPT_HEADER, 0);

            // Download the HTML from the URL.
            $content = curl_exec($ch);

            // Check to see if there were errors.
            if(curl_errno($ch)) {
                // We have an error. Show the error message.
                echo curl_error($ch);
            }
            else {
                // No error. Save the page content.
                $path = realpath(__DIR__ . '/../../');
                $file = $path . '/files/DownloadedHtml/' . basename($value) . '.html';

                // Open a file for writing.
                $fh = fopen($file, 'w');

                if(!$fh) {
                    // Couldn't create the file.
                    echo "Unable to create $file";
                }
                else {
                    // Write the retrieved html to the file.
                    fwrite($fh, $content);

                    // Close the file.
                    fclose($fh);
                }
            }

            // Close the curl handle.
            curl_close($ch);
            }

            
        }
        
        return $app['twig']->render('@front/homepage.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function login(Application $app) {
        return $app['twig']->render('@front/login.twig');
    }


    public function register(Application $app, Request $request) {
        $sent = false;
        //default data
        $data  = array(
            'name' => 'Your Name',
            'email' => 'Your email'
        );

        $form = $app['form.factory'] ->createBuilder('form')
            ->add('name', 'text', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' =>3))),
                'attr' => array('class' => 'form-control', 'placeholder' => 'Your Name')
            ))
            ->add('email', 'email', array(
                'constraints' => new Assert\Email(),
                'attr' => array('class' => 'form-control', 'placeholder' => 'Your@email.com')
            ))
            ->add('gender:', 'choice', array(
                'choices' => array('male' => 'Male', 'female' => 'Female'),
                'attr' => array('class' => 'form-control'),
                'expanded' => true
            ))
            ->add('interested-in:', 'choice', array(
                'choices' => array('male' => 'Male', 'female' => 'Female', 'both' => 'Both', 'none' => 'None'),
                'attr' => array('class' => 'form-control'),
                'multiple' => true,
                'expanded' => true
            ))
            ->add('dob', 'date', array(
                'attr' => array('class' => 'form-control'),
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd'

            ))
            ->add('comments', 'textarea', array(
                'empty_data' => null,
                'required' => false,
                'attr' => array('class' => 'form-control', 'placeholder' => 'Add your views')
            ))

            ->getForm();

        $form->handleRequest($request);

        if ('POST' == $request->getMethod()) {
            //$form->bind($request);

                if ($form->isValid()) {
                $data = $form->getData();
                
                try{
                    $app[ 'db' ]-> insert('register', array(
                        'email' => $data['email'],
                        'name' => $data['name'],
                        'gender' => $data['gender']
                        ));

                    return $app->redirect($app['url_generator']->generate('register'));
                }   catch(Exception $e){
                        $errori[] = "Error";
                    }
                }
        }




        return $app['twig']->render('@front/register.twig', array('form' => $form->createView()));
    }



    public function css(Application $app) {
        return $app['twig']->render('css.twig', array('time' => time()));
    }

    public function js(Application $app) {
        return $app['twig']->render('js.twig');
    }


}

class HttpCurl {
    private $_info, $_body;
     
    public function __construct() {
        if (!function_exists('curl_init')) {
            throw new Exception('cURL not enabled!');
        }  
    }
    public function get($url) {
        $this->request($url);
    }
    protected function request($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);       
        $this->_body = curl_exec($ch);
        $this->_info  = curl_getinfo($ch);
        $this->_error = curl_error($ch);
        curl_close($ch);   
    }
    public function getStatus() {
        return $this->_info['http_code'];
    }
     
    public function getHeader() {
        return $this->_info;
    }
    public function getBody() {
        return $this->_body;
    }
     
    public function __destruct() {
    }  
}



function url_to_absolute( $baseUrl, $relativeUrl )
{
    // If relative URL has a scheme, clean path and return.
    $r = split_url( $relativeUrl );
    if ( $r === FALSE )
        return FALSE;
    if ( !empty( $r['scheme'] ) )
    {
        if ( !empty( $r['path'] ) && $r['path'][0] == '/' )
            $r['path'] = url_remove_dot_segments( $r['path'] );
        return join_url( $r );
    }

    // Make sure the base URL is absolute.
    $b = split_url( $baseUrl );
    if ( $b === FALSE || empty( $b['scheme'] ) || empty( $b['host'] ) )
        return FALSE;
    $r['scheme'] = $b['scheme'];

    // If relative URL has an authority, clean path and return.
    if ( isset( $r['host'] ) )
    {
        if ( !empty( $r['path'] ) )
            $r['path'] = url_remove_dot_segments( $r['path'] );
        return join_url( $r );
    }
    unset( $r['port'] );
    unset( $r['user'] );
    unset( $r['pass'] );

    // Copy base authority.
    $r['host'] = $b['host'];
    if ( isset( $b['port'] ) ) $r['port'] = $b['port'];
    if ( isset( $b['user'] ) ) $r['user'] = $b['user'];
    if ( isset( $b['pass'] ) ) $r['pass'] = $b['pass'];

    // If relative URL has no path, use base path
    if ( empty( $r['path'] ) )
    {
        if ( !empty( $b['path'] ) )
            $r['path'] = $b['path'];
        if ( !isset( $r['query'] ) && isset( $b['query'] ) )
            $r['query'] = $b['query'];
        return join_url( $r );
    }

    // If relative URL path doesn't start with /, merge with base path
    if ( $r['path'][0] != '/' )
    {
        $base = mb_strrchr( $b['path'], '/', TRUE, 'UTF-8' );
        if ( $base === FALSE ) $base = '';
        $r['path'] = $base . '/' . $r['path'];
    }
    $r['path'] = url_remove_dot_segments( $r['path'] );
    return join_url( $r );
}

/**
 * Filter out "." and ".." segments from a URL's path and return
 * the result.
 *
 * This function implements the "remove_dot_segments" algorithm from
 * the RFC3986 specification for URLs.
 *
 * This function supports multi-byte characters with the UTF-8 encoding,
 * per the URL specification.
 *
 * Parameters:
 *  path    the path to filter
 *
 * Return values:
 *  The filtered path with "." and ".." removed.
 */
function url_remove_dot_segments( $path )
{
    // multi-byte character explode
    $inSegs  = preg_split( '!/!u', $path );
    $outSegs = array( );
    foreach ( $inSegs as $seg )
    {
        if ( $seg == '' || $seg == '.')
            continue;
        if ( $seg == '..' )
            array_pop( $outSegs );
        else
            array_push( $outSegs, $seg );
    }
    $outPath = implode( '/', $outSegs );
    if ( $path[0] == '/' )
        $outPath = '/' . $outPath;
    // compare last multi-byte character against '/'
    if ( $outPath != '/' &&
        (mb_strlen($path)-1) == mb_strrpos( $path, '/', 'UTF-8' ) )
        $outPath .= '/';
    return $outPath;
}


/**
 * This function parses an absolute or relative URL and splits it
 * into individual components.
 *
 * RFC3986 specifies the components of a Uniform Resource Identifier (URI).
 * A portion of the ABNFs are repeated here:
 *
 *  URI-reference   = URI
 *          / relative-ref
 *
 *  URI     = scheme ":" hier-part [ "?" query ] [ "#" fragment ]
 *
 *  relative-ref    = relative-part [ "?" query ] [ "#" fragment ]
 *
 *  hier-part   = "//" authority path-abempty
 *          / path-absolute
 *          / path-rootless
 *          / path-empty
 *
 *  relative-part   = "//" authority path-abempty
 *          / path-absolute
 *          / path-noscheme
 *          / path-empty
 *
 *  authority   = [ userinfo "@" ] host [ ":" port ]
 *
 * So, a URL has the following major components:
 *
 *  scheme
 *      The name of a method used to interpret the rest of
 *      the URL.  Examples:  "http", "https", "mailto", "file'.
 *
 *  authority
 *      The name of the authority governing the URL's name
 *      space.  Examples:  "example.com", "user@example.com",
 *      "example.com:80", "user:password@example.com:80".
 *
 *      The authority may include a host name, port number,
 *      user name, and password.
 *
 *      The host may be a name, an IPv4 numeric address, or
 *      an IPv6 numeric address.
 *
 *  path
 *      The hierarchical path to the URL's resource.
 *      Examples:  "/index.htm", "/scripts/page.php".
 *
 *  query
 *      The data for a query.  Examples:  "?search=google.com".
 *
 *  fragment
 *      The name of a secondary resource relative to that named
 *      by the path.  Examples:  "#section1", "#header".
 *
 * An "absolute" URL must include a scheme and path.  The authority, query,
 * and fragment components are optional.
 *
 * A "relative" URL does not include a scheme and must include a path.  The
 * authority, query, and fragment components are optional.
 *
 * This function splits the $url argument into the following components
 * and returns them in an associative array.  Keys to that array include:
 *
 *  "scheme"    The scheme, such as "http".
 *  "host"      The host name, IPv4, or IPv6 address.
 *  "port"      The port number.
 *  "user"      The user name.
 *  "pass"      The user password.
 *  "path"      The path, such as a file path for "http".
 *  "query"     The query.
 *  "fragment"  The fragment.
 *
 * One or more of these may not be present, depending upon the URL.
 *
 * Optionally, the "user", "pass", "host" (if a name, not an IP address),
 * "path", "query", and "fragment" may have percent-encoded characters
 * decoded.  The "scheme" and "port" cannot include percent-encoded
 * characters and are never decoded.  Decoding occurs after the URL has
 * been parsed.
 *
 * Parameters:
 *  url     the URL to parse.
 *
 *  decode      an optional boolean flag selecting whether
 *          to decode percent encoding or not.  Default = TRUE.
 *
 * Return values:
 *  the associative array of URL parts, or FALSE if the URL is
 *  too malformed to recognize any parts.
 */
function split_url( $url, $decode=FALSE)
{
    // Character sets from RFC3986.
    $xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
    $xpchar        = $xunressub . ':@% ';

    // Scheme from RFC3986.
    $xscheme        = '([a-zA-Z][a-zA-Z\d+-.]*)';

    // User info (user + password) from RFC3986.
    $xuserinfo     = '((['  . $xunressub . '%]*)' .
                     '(:([' . $xunressub . ':%]*))?)';

    // IPv4 from RFC3986 (without digit constraints).
    $xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

    // IPv6 from RFC2732 (without digit and grouping constraints).
    $xipv6         = '(\[([a-fA-F\d.:]+)\])';

    // Host name from RFC1035.  Technically, must start with a letter.
    // Relax that restriction to better parse URL structure, then
    // leave host name validation to application.
    $xhost_name    = '([a-zA-Z\d-.%]+)';

    // Authority from RFC3986.  Skip IP future.
    $xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
    $xport         = '(\d*)';
    $xauthority    = '((' . $xuserinfo . '@)?' . $xhost .
                 '?(:' . $xport . ')?)';

    // Path from RFC3986.  Blend absolute & relative for efficiency.
    $xslash_seg    = '(/[' . $xpchar . ']*)';
    $xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
    $xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
    $xpath_abs     = '(/(' . $xpath_rel . ')?)';
    $xapath        = '(' . $xpath_authabs . '|' . $xpath_abs .
             '|' . $xpath_rel . ')';

    // Query and fragment from RFC3986.
    $xqueryfrag    = '([' . $xpchar . '/?' . ']*)';

    // URL.
    $xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' .
                     '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


    // Split the URL into components.
    if ( !preg_match( '!' . $xurl . '!', $url, $m ) )
        return FALSE;

    if ( !empty($m[2]) )        $parts['scheme']  = strtolower($m[2]);

    if ( !empty($m[7]) ) {
        if ( isset( $m[9] ) )   $parts['user']    = $m[9];
        else            $parts['user']    = '';
    }
    if ( !empty($m[10]) )       $parts['pass']    = $m[11];

    if ( !empty($m[13]) )       $h=$parts['host'] = $m[13];
    else if ( !empty($m[14]) )  $parts['host']    = $m[14];
    else if ( !empty($m[16]) )  $parts['host']    = $m[16];
    else if ( !empty( $m[5] ) ) $parts['host']    = '';
    if ( !empty($m[17]) )       $parts['port']    = $m[18];

    if ( !empty($m[19]) )       $parts['path']    = $m[19];
    else if ( !empty($m[21]) )  $parts['path']    = $m[21];
    else if ( !empty($m[25]) )  $parts['path']    = $m[25];

    if ( !empty($m[27]) )       $parts['query']   = $m[28];
    if ( !empty($m[29]) )       $parts['fragment']= $m[30];

    if ( !$decode )
        return $parts;
    if ( !empty($parts['user']) )
        $parts['user']     = rawurldecode( $parts['user'] );
    if ( !empty($parts['pass']) )
        $parts['pass']     = rawurldecode( $parts['pass'] );
    if ( !empty($parts['path']) )
        $parts['path']     = rawurldecode( $parts['path'] );
    if ( isset($h) )
        $parts['host']     = rawurldecode( $parts['host'] );
    if ( !empty($parts['query']) )
        $parts['query']    = rawurldecode( $parts['query'] );
    if ( !empty($parts['fragment']) )
        $parts['fragment'] = rawurldecode( $parts['fragment'] );
    return $parts;
}


/**
 * This function joins together URL components to form a complete URL.
 *
 * RFC3986 specifies the components of a Uniform Resource Identifier (URI).
 * This function implements the specification's "component recomposition"
 * algorithm for combining URI components into a full URI string.
 *
 * The $parts argument is an associative array containing zero or
 * more of the following:
 *
 *  "scheme"    The scheme, such as "http".
 *  "host"      The host name, IPv4, or IPv6 address.
 *  "port"      The port number.
 *  "user"      The user name.
 *  "pass"      The user password.
 *  "path"      The path, such as a file path for "http".
 *  "query"     The query.
 *  "fragment"  The fragment.
 *
 * The "port", "user", and "pass" values are only used when a "host"
 * is present.
 *
 * The optional $encode argument indicates if appropriate URL components
 * should be percent-encoded as they are assembled into the URL.  Encoding
 * is only applied to the "user", "pass", "host" (if a host name, not an
 * IP address), "path", "query", and "fragment" components.  The "scheme"
 * and "port" are never encoded.  When a "scheme" and "host" are both
 * present, the "path" is presumed to be hierarchical and encoding
 * processes each segment of the hierarchy separately (i.e., the slashes
 * are left alone).
 *
 * The assembled URL string is returned.
 *
 * Parameters:
 *  parts       an associative array of strings containing the
 *          individual parts of a URL.
 *
 *  encode      an optional boolean flag selecting whether
 *          to do percent encoding or not.  Default = true.
 *
 * Return values:
 *  Returns the assembled URL string.  The string is an absolute
 *  URL if a scheme is supplied, and a relative URL if not.  An
 *  empty string is returned if the $parts array does not contain
 *  any of the needed values.
 */
function join_url( $parts, $encode=FALSE)
{
    if ( $encode )
    {
        if ( isset( $parts['user'] ) )
            $parts['user']     = rawurlencode( $parts['user'] );
        if ( isset( $parts['pass'] ) )
            $parts['pass']     = rawurlencode( $parts['pass'] );
        if ( isset( $parts['host'] ) &&
            !preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
            $parts['host']     = rawurlencode( $parts['host'] );
        if ( !empty( $parts['path'] ) )
            $parts['path']     = preg_replace( '!%2F!ui', '/',
                rawurlencode( $parts['path'] ) );
        if ( isset( $parts['query'] ) )
            $parts['query']    = rawurlencode( $parts['query'] );
        if ( isset( $parts['fragment'] ) )
            $parts['fragment'] = rawurlencode( $parts['fragment'] );
    }

    $url = '';
    if ( !empty( $parts['scheme'] ) )
        $url .= $parts['scheme'] . ':';
    if ( isset( $parts['host'] ) )
    {
        $url .= '//';
        if ( isset( $parts['user'] ) )
        {
            $url .= $parts['user'];
            if ( isset( $parts['pass'] ) )
                $url .= ':' . $parts['pass'];
            $url .= '@';
        }
        if ( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) )
            $url .= '[' . $parts['host'] . ']'; // IPv6
        else
            $url .= $parts['host'];         // IPv4 or name
        if ( isset( $parts['port'] ) )
            $url .= ':' . $parts['port'];
        if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
            $url .= '/';
    }
    if ( !empty( $parts['path'] ) )
        $url .= $parts['path'];
    if ( isset( $parts['query'] ) )
        $url .= '?' . $parts['query'];
    if ( isset( $parts['fragment'] ) )
        $url .= '#' . $parts['fragment'];
    return $url;
}

/**
 * This function encodes URL to form a URL which is properly 
 * percent encoded to replace disallowed characters.
 *
 * RFC3986 specifies the allowed characters in the URL as well as
 * reserved characters in the URL. This function replaces all the 
 * disallowed characters in the URL with their repective percent 
 * encodings. Already encoded characters are not encoded again,
 * such as '%20' is not encoded to '%2520'.
 *
 * Parameters:
 *  url     the url to encode.
 *
 * Return values:
 *  Returns the encoded URL string. 
 */
function encode_url($url) {
  $reserved = array(
    ":" => '!%3A!ui',
    "/" => '!%2F!ui',
    "?" => '!%3F!ui',
    "#" => '!%23!ui',
    "[" => '!%5B!ui',
    "]" => '!%5D!ui',
    "@" => '!%40!ui',
    "!" => '!%21!ui',
    "$" => '!%24!ui',
    "&" => '!%26!ui',
    "'" => '!%27!ui',
    "(" => '!%28!ui',
    ")" => '!%29!ui',
    "*" => '!%2A!ui',
    "+" => '!%2B!ui',
    "," => '!%2C!ui',
    ";" => '!%3B!ui',
    "=" => '!%3D!ui',
    "%" => '!%25!ui',
  );

  $url = rawurlencode($url);
  $url = preg_replace(array_values($reserved), array_keys($reserved), $url);
  return $url;
}





