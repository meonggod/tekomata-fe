<?php

/*
|--------------------------------------------------------------------------
| Supported locales  ← THE bilingual control panel
|--------------------------------------------------------------------------
| This is the ONE place that decides which languages the site offers and the
| label shown in the language switcher. To add/rename/remove a language, edit
| the 'supported' map below AND create a matching folder in lang/<code>/.
|
| The DEFAULT language is APP_LOCALE in your .env (currently "id"). The
| FALLBACK (used when a phrase is missing) is APP_FALLBACK_LOCALE ("en").
|
| The actual wording lives in:
|   lang/en/messages.php   ← English text
|   lang/id/messages.php   ← Indonesian text
| Edit those files to fix any translation.
*/

return [

    'supported' => [
        'id' => 'Bahasa Indonesia',
        'en' => 'English',
    ],

];
