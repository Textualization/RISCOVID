# RISCOVID: COVID-19 Risk Self-Assessment and Disclousure forms

Tool for generating COVID-19 risk self-assessment and disclousre
statements.  These HTML forms help people make informed decisiones
regarding social gatherings. They are serverless and
self-contained. They include a natural language generation component
that writes language summaries of the form.

RISCOVID builds on top of
[NLGen](https://packagist.org/packages/nlgen/nlgen) and compiles
aggregated output as a JavaScript lookup in the generated HTML forms.

The forms are currently available in English and Spanish. Translations
welcomed.

Licensed under the AGPL-v3.

## Example Form

The HTML forms contains questions such as:

* How many people would you estimate are in the "social bubble" of
  your household (people seen by somebody in your household, including
  yourself, at least once a month)?

  * More than 50.
  * 10-50.
  * 6-10.
  * 1-5.
  * Just us.
  
In the generated summary, if the person chooses "1-5" the text will
include "We keep to ourselves".

The default forms contain 10+1 questions, but a live website allows to
create your own customized versions.

## Live Tool

This tool is live at [http://textualization.com/riscovid](http://textualization.com/riscovid).
