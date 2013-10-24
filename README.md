# Joomla! - Code prettify plugin

**Version:** 1.1.0  

System plugin for code highlighting based on the [Google Code Prettify](http://google-code-prettify.googlecode.com) library  

## Google Code Prettify features

* Template support
* Automatically language detection 
* Used by code.google.com and stackoverflow.com
* More details in the [project wiki](http://code.google.com/p/google-code-prettify/wiki/GettingStarted)

## Joomla! plugin features

* You can override / customise templates just copying them to your template folder
* Uses standard javascript `onload` event so it works with `jQuery`, `Mootools` or any other library
* It's only 14KB 

## Installation 

1. Download the plugin 
2. In plugin management search and enable the plugin `codeprettify`. You can also select the template there.
3. Start using it!

## Use

To start adding code you only need to create a `<pre class="prettyprint">` block with your coding inside like:  

`<pre class="prettyprint">
    // Required objects
    $app    = JFactory::getApplication();
    $db     = JFactory::getDbo();
    $jinput = $app->input;
</pre>`

## Customise/create new templates

The easiest way to create a template is base it in any of the existing templates.  

To do that copy the desired style from the folder:  
`media/codeprettify/styles` 

to the folder:
`template/YOUR_TEMPLATE/html/plg_system_codeprettify/styles`  

And then edit there to fit your needs. In the plugin settings your new template will be shown in the select list marked as `(override)`  
