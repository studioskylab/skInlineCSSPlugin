skInlineCSSPlugin
=================

This symfony plugin contains the skInlineCSS class, which takes an HTML document and CSS, and moves all the CSS properties into inline `style` attributes on the relevant elements.

We use this plugin to move styles inline for HTML emails.



Usage
-----

CSS can either be contained in `<style />` or linked to in `<link rel="stylesheet" />` elements within the HTML, or can be passed in separately.

	<?php
	$c = new skInlineCSS(array(/* options */));
	echo $c->convert($html, $css);

For more detail, see the docblocks in `lib/skInlineCSS.class.php`.



License and Attribution
-----------------------

skInlineCSSPlugin by [Studio Skylab](http://www.studioskylab.com)

This code is released under the [Creative Commons Attribution-ShareAlike 3.0 License](http://creativecommons.org/licenses/by-sa/3.0/).

The `skInlineCSS` class is based on [CssToInlineStyles](https://github.com/tijsverkoyen/CssToInlineStyles) by Tijs Verkoyen.
The `skCSSSelectorXPathConverter` class is a loose PHP port of part of the old selector engine from [Prototype.js](http://www.prototypejs.org/).

Original CssToInlineStyles License
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Copyright©, Tijs Verkoyen. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
* Neither the name of Tijs Verkoyen, nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

This software is provided by the copyright holders and contributors as is and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the copyright owner or contributors be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.

Original Prototype.js License
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Copyright (c) 2005-2008 Sam Stephenson

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

Portions of the Selector class in Prototype.js are derived from Jack
Slocum's DomQuery, part of YUI-Ext version 0.40, distributed under the terms
of an MIT-style license. Please see http://www.yui-ext.com/ for
more information.