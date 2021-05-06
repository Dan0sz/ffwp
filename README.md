# Custom Additions to EDD for FFWP.dev

This WordPress plugin contains all custom additions I made to tailor Easy Digital Downloads to my wishes for usage with [Fast FW Press](https://ffwp.dev).

Sharing this publicly for educational purposes.

## Reusable License Key

This plugin allows you to specify a license key that you wish to reuse endlessly for a specified EDD download.

Easy Digital Downloads Software Licensing only allows pre-specified keys or randomly generated keys to be used, but what if you want a certain (free) download to use the same key over and over again? That's not possible.

To use EDD Software Licensing's automatic update feature (e.g. with EDD GIT Updater) a license key is required. But what if it's e.g. a free download and you want to save people the hassle of having to activate it using a license key?

Using a static license key, you could programmatically activate the license, saving your customers the hassle.

### Usage

`includes/reusable-license/class-generate.php` contains two variables: 

- `$license_key`: the license key you wish to use.
- `$item_id`: the ID of the download you wish to apply the license key on.

After setting these two variables, the specified `$license_key` will be applied with every order of `$item_id`.

Enjoy!

## Auto Add To Cart
   
For my premium WordPress plugins I use a separate plugin to manage licenses.
   
This plugin automatically adds FFWP License Manager to the customer's cart, when adding another product. It also prevents it from being removed from the cart.

### Usage
   
The item's ID can easily be changed by changing the variable `$item_id` on line 17 in `includes/auto-add-to-cart/class-process.php`. I'll make this dynamic in the future.
   
This plugin could also be used for free giveaways, gifts with each purchase, freebies, etc.
   
See it in action at [ffwp.dev](https://ffwp.dev).

## Changelog Shortcode

This plugin for WordPress adds a shortcode which accepts a (Easy Digital Downloads) Download ID as a parameter and prints the changelog (generated by Software Licensing).

### Usage

`[changelog id="132"]`

That's it!

## Child Pages Menu Shortcode

This plugin adds a shortcode to WordPress which renders an unordered list of a specified (`slug`) parent page.

If a `slug` isn't specified, it attempts to find a page using the last part of the current URI, combined with the `base` paremeter.

### Usage

`[child_pages_menu slug="my-page" base="knowledge-base"]`

This shortcode will return the children pages of a page located at `knowledge-base/my-page`.

If a slug is not specified, e.g. `[child_pages_menu base="animals"]` and this shortcode is called on https://yourdomain.com/awesome-stuff/cool-hamsters, then it will attempt to render the child pages of a page located at `animals/cool-hamsters`.

You can also hide some child pages by specifying their ID's in a comma separated list, e.g. 

`[child_page_menu slug="my-page" base="kb" hide="1,2,3"]`

This will not render child pages with ID's 1, 2 and 3.

As a last fallback it will try to get child pages based on the `base` parameter.

That's it!

## Current Plugin Version Shortcode

It does what the title says, it adds a shortcode which outputs the latest version of a defined plugin.

The code is copied directly from [here](https://hawpmedia.com/how-to-get-easy-digital-downloads-product-version-number-with-shortcode/).

### Usage 

Insert shorcode `[edd_product_version id="post_id_here"]` to display the version number of a defined product.

## Non-required Card State Field

Dynamically detect if State should be a required field, based on the selected Country.

For any country with a predefined list of states, e.g. US and CA, the State field is a required field. For any other country, where the State field is a text box, it becomes a non-required entry.

Inspired by and an enhanced version of: http://library.easydigitaldownloads.com/checkout/non-required-card-state.html 

### Usage

Install. 

Enjoy!

## Custom Checkout Fields

Adds custom Project Information fields to its checkout, purchase confirmation, order confirmation email and payment details screens when defined items are purchased. This includes validation, etc.

Loosely based on [this tutorial](https://scottdeluzio.com/add-custom-field-to-easy-digital-downloads-checkout/). Sharing it here for educational purposes. Fork it and do as you please!

### Usage

* Install,
* Define download ID's 
* Enjoy!

## Login Fields Legend and Description

Adds a custom translatable legend and description to the login form in checkout.

## Better Checkout

Heavily customizes the otherwise rather boring checkout flow for Easy Digital Download's checkout.

### Overview

- Personal details and billing information is moved to the top,
- A separate right column for the shopping cart,
- Loading animations when e.g. tax fields are refreshed,
- Discount form is moved below shopping cart,
- Renew license (when Software License add-on is used) is moved to the top of the screen, along with the login form, which is hidden under a popup,
- Payment methods are shown in big buttons, spread among 2 columns, along with logos.
