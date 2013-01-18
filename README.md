# Freeform Values
By default, if you want Freeform to preserve your field values after an error, you must use the `{freeform:field:FIELD_NAME}` tag, which outputs the complete HTML tag for your form field.

This is all well and good, until you want to add something other than a text field or textarea to your form. At that point, you either forego the simple ambition of preserving the previously-submitted form values, or you shell out $99.95 for [Freeform Pro][ff_pro] (and excellent product in many ways, but not if all you want is a checkbox, for example).

[ff_pro]: http://www.solspace.com/software/detail/freeform/ "Solspace's premium form builder"

This is where Freeform Values comes in. It hooks into the standard Freeform hooks, and makes it possible to do this:

`````html
<input name="contact_email" type="email" value="{freeform:value:contact_email}">
`````

If the form has been submitted (and failed validation), the `{freeform:value:FIELD_NAME}` template tag is set to the previously-submitted value. Otherwise, it's set to an empty string.

You can even use the tag in conditionals, like this:

`````php
<input name="newsletter" type="checkbox" value="Y"
  {if '{freeform:value:newsletter}' == 'Y'}checked{/if}>
`````

## Caveats and whatnot
I haven't tested this add-on with Freeform Pro (that was the whole point of writing it, after all).

In fact, I haven't tested it on anything other than my own site, which at time of writing is running PHP 5.3.10 and EE 2.5.5. It should work fine on PHP 5.3.x and above, but there are no guarantees.

This is also offered without any support whatsoever. Feel free to fork if you need it to dance to your tune.