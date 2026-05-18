<?php
// Copie este arquivo para includes/openai.local.php e informe sua chave.
// O arquivo openai.local.php fica no .gitignore.
//
// Provedores suportados:
// - openai
// - gemini

define('AI_PROVIDER', 'gemini');

define('OPENAI_API_KEY', '');
define('OPENAI_MODEL', 'gpt-5.1');

define('GEMINI_API_KEY', '');
define('GEMINI_MODEL', 'gemini-2.5-flash');
define('GEMINI_FALLBACK_MODELS', 'gemini-2.0-flash-lite,gemini-2.0-flash');
