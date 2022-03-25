# WWOPTIONS
Small custom library for rendering and processing options (including tabs and option pages) Woo+WP native way

> WARNING

This is not a plugin or something! A php script to include to your project for fast and automated way to create/render
WP option pages with/without tabs or WooCommerce setting tabs with options.

## Usage

There are 2 global functions to use: `wwp_options([])` - for WP option page(s), and `wwc_options([])` - for Woo option 
tab(s). 

### Notes

 - this was meant to be a *SINGLE* PHP file small library - dont yell at me!

 - both functions - `wwp_options` and `wwc_options` must be called not later than `woocommerce_init` hook

 - `slugs` here must be unique across whole WP options scope

 - page, tab and section slugs are never used in WP options storage and only participate in UI representation

 - structure of options is linear (one after another). All options go to the tab/page defined above/before.

# wwp_options
 - Function `wwp_options( $options_pages )` - creates a WordPress options page with a set of options or set of tabs with 
options.
 
> Syntax:
```
wwp_options([
 'page1_slug' => [
     'title'         => 'Options', // only text. Used as page <title> too.
     'before'        => '<html>', // html/text to render after the title on the settings page
     'after'         => '<html>', // html/text to render after the title on the settings page
     'description'   => '', // html/text to render as page description (below title)
     'menu'          => 'Menu entry title', // optional. only text
     'save_button'   => 'Save changes', // optional - text for save button or false for not rendering it
     'save_notice'   => 'Your options saved successfully!', // optional - notice upon save action. By default 
                                                            // not shown. Supports HTML.
     'type'          => 'page'
     'styles'        => [ // or 'scripts'. Optional. Note: all admin scripts non-cachable.
         'slug' => [
             'type'      => 'file', // or 'inline'. Default to 'file'. Optional.
             'source'    => 'https://url.com/some/file.css', // or regular css - will be wrapped into <style>
             'global'    => false, // Optional. Whenever we enqueue it for all admin area. Default to false.
             'footer'    => false, // Optional. Default to false. If we render/enqueue it in the footer.
             'localize'  => [
                 '_object_name' => [] // Optional. Object data to localize.
             ],
             'deps'      => [] // Optional. Dependencies. E.g. 'jquery' for 'scripts', 'type' => 'file'.
         ],
    
     ],
 ]
 'tab1_slug' => [
     'type' => 'tab',
     'title => 'General',
 ],
 'section_general' => [
     'type'  => 'section',
     'title' => 'General options',
     'description' => 'General plugin options',
 ],
 'option_slug_1' => [
     'type'  => 'email',
     'title' => 'Your email', // supports html
     'label' => '',
     'placeholder' => '',
     'custom_attributes' => [ 'attribute' => 'value', ],
     'css' => '', // style="",
     'class' => '',
     'default' => '',
     'value' => '' // by default will be set from current options, use this to override,
     'description' => 'Some description', // supports html
     'hint' => '', // title attribute,
     'before' => '', // html to insert before the input element
     'after' => '', // html to insert after the input element
 ],

 // same for all other types, additional params below

 'option_slug_2' => [
     'type' => 'select' | 'radio',
     // ... all same params as above, plus: //
     'options' => [
         'value' => 'Option label'
     ]
 ],

 'option_slug_3' => [
     'type' => 'checkbox',
     // ... all same params as above, plus: //
     'default' => 'yes' | 'no' | '',
 ],

 'option_slug_4' => [
     'type' => 'number',
     // ... all same params as above, plus: //
     'step' => '1',
     'min' => '1',
     'max' => '100',
     'pattern' => '[0-9]',
 ],

 'section_general_end' => [
     'type' => 'section_end', // renders <hr/> element
 ],

 'page_slug_2' => []...
]);
```

 - possible 'type' values:   'tab', 'section', 'section_end', 'select', 'text', 'number', 'checkbox',
                             'radio', 'textarea', 'email', 'password', 'button', 'custom', 'page', 'group'

 - 'group'  renders its options as controls separated with new line

 - 'custom' represents custom html, it's 'value' is rendered as html. May be useful for rendering JS-driven options.
            Additionally, to avoid action happening "too early", 'value' may be callable and return html when needed.
            E.g. 'value' => 'some html' OR 'value' => [ $this, 'public_method' ] OR 'value' => 'some_function'.
            *Note* if you render checkbox, render 'hidden' input with the same name and opposite value above the checkbox!

 - By default, all page tabs switching is driven by JS (all tabs are on same page), to make it as separate pages,
   define 'separate_tabs' => 'yes' as 'page' parameter.


# wwc_options

>Function `wwc_options( $options_tabs )`

- supports same syntax as above ( no page type! Start with 'tab' type ) - adds Woo settings tabs

- The settings are rendered by regular Woo function. Use corresponding supported by Woo types of inputs (no custom, no button).

- Supports 'styles'/'scripts' on 'tab' type (see above). But no 'after', 'before' or 'description' as this is not supported by Woo.

# Additional parameters:
- 'priority' => 1, // WP options 'page' parameter for the admin options menu priority to insert page. Defaults to null.
                      For Woo settings 'tab' type it also defines the add_action() hook priority. Defaults to 99.

- 'before' | 'after' => 'slug', // Woo settings tab slug to be used to insert your tab

>Notes
- If you are using 'custom' type, you supposed to use your own JS to make it work

- We do not filter "title" parameter, html is supported

## Example 

For example on initialization and usage - see 'init-example.php' in this repo.

#### Thats it! Happy coding :)

### Version info

- 1.0 
  - Initial (2022-01-18, Stim). Tested with nShift Unifaun refactored plugin.
