<?php

/*
 * inspiration from:
 * https://github.com/jasny/twig-extensions
 */

namespace Bsik\Render;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TemplatingExtension extends AbstractExtension
{

    /**
     * Return extension name
     *
     * @return string
     */
    public function getName()
    {
        return 'Bsik/Render/TemplatingExtension';
    }

    public function getFunctions(): array
    {

        // options  : [
        //     //'is_safe' => ['html'], //whether to skip escaping or not 
        //     //'needs_context'     => false, //Passes the template context (all the variable used)
        //     //'needs_environment' => true, //Passes the $env to the function
        //     //'is_variadic'       => flase // https://stackoverflow.com/questions/50621564/does-twig-support-variadic-arguments-using-the-token
        // ]
        return [
            new TwigFunction(
                name     : 'render_as_attributes', 
                callable : [$this, 'render_array_as_attributes'], 
            ),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter(
                name     : 'array_values', 
                callable : [$this, 'return_array_values'], 
            ),
            new TwigFilter(
                name     : 'array_keys', 
                callable : [$this, 'return_array_keys'], 
            ),
        ];
    }

    /**
     * Return all the values of an array or object
     *
     * @param array|object $array
     * @return array
     */
    public function return_array_values($input)
    {
        return isset($input) ? array_values((array)$input) : null;
    }

    /**
     * Return all the values of an array or object
     *
     * @param array|object $array
     * @return array
     */
    public function return_array_keys($input)
    {
        return isset($input) ? array_keys((array)$input) : null;
    }

    /**
     * Cast an array to an HTML attribute string
     *
     * @param mixed $array
     * @return string
     */
    public function render_array_as_attributes($array)
    {
        if (empty($array)) return null;
        $str = "";
        foreach ($array as $key => $value) {
            if (!isset($value) || $value === false)
                continue;
            if ($value === true) 
                $value = $key;
            $str .= ' ' . $key . '="' . addcslashes($value, '"') . '"';
        }
        return trim($str);
    }

}