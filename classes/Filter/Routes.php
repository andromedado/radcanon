<?php

class FilterRoutes implements Filter
{
    const CACHED_ROUTES_KEY = 'cached_routes';
    protected static $DeniedRoutes = array(
    );
    protected static $PregRoutes = array(
        'example' => array(
            'decode' => array(
                'pattern' => '#^example-example/(\d+)$#',
                'controller' => 'Example',
                'action' => 'example',
                'arguments' => array('$1'),
            ),
            'encode' => array(
                'pattern' => 'example-example/%3$d',
                'controller' => '#^Example#',
                'action' => '#^example#',
                'arguments' => array('#^\d+$#'),
            ),
        ),
    );
    protected static $Routes = array(
        '' => array(
            'controller' => 'Pages',
            'action' => 'homepage',
        ),
        '404-not-found' => array(
            'controller' => 'Pages',
            'action' => 'notFound',
        ),
    ),
    $externalRoutes = array();

    /**
     * @param $url
     * @param $destination
     */
    public static function addExternalRoute($url, $destination)
    {
        self::$externalRoutes[$url] = $destination;
    }

    /**
     * Add a static route
     * @param String $url
     * @param Array $path
     */
    public static function addRoute ($url, array $path) {
        if (!isset($path['controller'])) {
            $oldPath = $path;
            $path = array('controller' => array_shift($oldPath));
            if (!empty($oldPath)) {
                $path['action'] = array_shift($oldPath);
                if (!empty($oldPath)) {
                    if (is_array(current($oldPath))) {
                        $path['arguments'] = array_shift($oldPath);
                    } else {
                        $path['arguments'] = $oldPath;
                    }
                }
            }
        }
        self::$Routes[$url] = $path;
    }

    public function filter(Request $req, Response $res, User $user) {
        $uri = $req->getURI();
        if (in_array($uri, self::$DeniedRoutes)) {
            $req->setURI($uri = 'Pages/notFound');
        }
        if (array_key_exists($uri, self::$externalRoutes)) {
            $res->redirectTo(self::$externalRoutes[$uri]);
            $res->redirectCode = 301;
            return;
        }
        foreach (self::$PregRoutes as $bits) {
            if (preg_match($bits['decode']['pattern'], $uri)) {
                $truePath = $bits['decode'];
                unset($truePath['pattern']);
                if (isset($truePath['arguments']) && is_array($truePath['arguments'])) {
                    $truePath['arguments'] = implode('/', $truePath['arguments']);
                }
                $req->setURI($uri = preg_replace($bits['decode']['pattern'], implode('/', $truePath), $uri));
                break;
            }
        }
        if (array_key_exists($uri, self::$Routes)) {
            $info = self::$Routes[$uri];
            if (isset($info['arguments']) && is_array($info['arguments'])) {
                $info['arguments'] = implode('/', $info['arguments']);
            }
            $req->setURI(implode('/', $info));
        }
    }

    public static function buildUrl (array $info, $relative = false, $useShortcuts = true) {
        $Info = array();
        $info = array_map('urlencode', $info);
        if (!empty($info)) {
            $Info['controller'] = array_shift($info);
            if ($info) {
                $Info['action'] = array_shift($info);
            }
            if ($info) {
                $Info['arguments'] = $info;
            }
        }
        $path = $Info;
        if (isset($Info['arguments']) && is_array($Info['arguments'])) {
            $Info['arguments'] = implode('/', $Info['arguments']);
        }
        $suffix = implode('/', $Info);
        if ($useShortcuts) {
            foreach (self::$Routes as $Suffix => $Route) {
                $route = $Route;
                if (isset($route['arguments']) && is_array($route['arguments'])) {
                    $route['arguments'] = implode('/', $route['arguments']);
                }
                if ($route === $Info) {
                    $path = $Route;
                    $suffix = $Suffix;
                    break;
                }
            }
            if (!isset($path['arguments'])) $path['arguments'] = array();
            foreach (self::$PregRoutes as $bits) {
                if (isset($path['controller']) && isset($path['action']) && count($path['arguments']) === count($bits['encode']['arguments'])) {
                    if (preg_match($bits['encode']['controller'], $path['controller']) && preg_match($bits['encode']['action'], $path['action'])) {
                        $continue = true;
                        foreach ($bits['encode']['arguments'] as $k => $pattern) {
                            $continue = $continue && preg_match($pattern, $path['arguments'][$k]);
                        }
                        if ($continue) {
                            $args = $path['arguments'];
                            array_unshift($args, $path['action'], $path['controller']);
                            $suffix = vsprintf($bits['encode']['pattern'], $args);
                            break;
                        }
                    }
                }
            }
        }
        if ($relative) return $suffix;
        return APP_SUB_DIR . '/' . $suffix;
    }

}

