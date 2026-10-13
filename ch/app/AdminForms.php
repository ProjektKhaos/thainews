<?php
declare(strict_types=1);
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(404);
    exit;
}

function ae(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function au(string $view = 'dashboard', array $parameters = []): string {
    return url('admin.php') . '?' . http_build_query(['view' => $view] + $parameters);
}

function money(mixed $value): string {
    return number_format((int)$value / 100, 2, '.', ',') . ' THB';
}

function submitted_field(string $name, mixed $fallback): mixed {
    if (empty($GLOBALS['admin_form_repopulate'])) return $fallback;
    if (!preg_match('/^([a-z0-9_-]+)(?:\[([a-z0-9_-]+)\])?$/i', $name, $parts)) return $fallback;
    $value = $_POST[$parts[1]] ?? null;
    if (isset($parts[2])) $value = is_array($value) ? ($value[$parts[2]] ?? null) : null;
    return is_scalar($value) ? (string)$value : $fallback;
}

function form_start(string $action, string $view, array $hidden = [], bool $multipart = false): void {
    global $csrf, $error;
    $postedAction = (string)($_POST['action'] ?? (isset($_POST['username'], $_POST['password']) ? 'login' : ''));
    $GLOBALS['admin_form_repopulate'] = $_SERVER['REQUEST_METHOD'] === 'POST' && $error !== ''
        && $postedAction === $action
        && (string)($_POST['id'] ?? '') === (string)($hidden['id'] ?? '')
        && (string)($_POST['scope'] ?? '') === (string)($hidden['scope'] ?? '');
    $context = [];
    if (isset($hidden['return_id'])) $context['id'] = (int)$hidden['return_id'];
    if (isset($hidden['scope'])) $context['scope'] = (string)$hidden['scope'];
    if (isset($hidden['return_new'])) $context['new'] = 1;
    if ($view === 'calendar') {
        foreach (['date', 'mode'] as $key) if (isset($_GET[$key]) && is_string($_GET[$key])) $context[$key] = $_GET[$key];
    }
    echo '<form method="post" action="' . ae(au($view, $context)) . '"'
        . ($multipart ? ' enctype="multipart/form-data"' : '') . '>';
    foreach (['csrf' => $csrf, 'action' => $action, 'return_view' => $view] + $hidden as $key => $value) {
        echo '<input type="hidden" name="' . ae($key) . '" value="' . ae($value) . '">';
    }
}

function input(string $name, string $label, mixed $value = '', string $type = 'text', bool $required = false, string $extra = ''): void {
    if ($type !== 'password') $value = submitted_field($name, $value);
    echo '<label>' . ae($label) . '<input type="' . ae($type) . '" name="' . ae($name)
        . '" value="' . ae($value) . '"' . ($required ? ' required' : '') . ' ' . $extra . '></label>';
}

function textarea(string $name, string $label, mixed $value = '', int $rows = 4): void {
    $value = submitted_field($name, $value);
    $max = $name === 'note' ? 2000 : (str_starts_with($name, 'content[') ? 5000 : 6000);
    echo '<label>' . ae($label) . '<textarea name="' . ae($name) . '" rows="' . $rows
        . '" maxlength="' . $max . '">' . ae($value) . '</textarea></label>';
}

function select(string $name, string $label, array $options, mixed $selected = ''): void {
    $selected = submitted_field($name, $selected);
    echo '<label>' . ae($label) . '<select name="' . ae($name) . '">';
    foreach ($options as $value => $text) {
        echo '<option value="' . ae($value) . '"' . ((string)$value === (string)$selected ? ' selected' : '')
            . '>' . ae($text) . '</option>';
    }
    echo '</select></label>';
}

function checkbox(string $name, string $label, bool $checked = false): void {
    if (!empty($GLOBALS['admin_form_repopulate'])) $checked = submitted_field($name, '') === '1';
    echo '<label class="check"><input type="checkbox" name="' . ae($name) . '" value="1"'
        . ($checked ? ' checked' : '') . '>' . ae($label) . '</label>';
}

function friendly_error(string $message): string {
    return strtr($message, [
        'Datumen är upptagna.' => 'The selected dates are unavailable.',
        'Datumen är inte tillgängliga.' => 'The selected dates are unavailable.',
        'Statusövergången är inte tillåten.' => 'This status change is not allowed.',
        'Reservationen har gått ut.' => 'The reservation has expired.',
        'Ogiltig period.' => 'Choose a valid date range.',
        'Välj giltiga datum och vistelselängd.' => 'Choose dates within the configured stay limits.',
        'Ogiltigt antal gäster.' => 'Choose a guest count within the configured capacity.',
        'Priser är inte godkända.' => 'Set and review the base price first.',
        'Bokningen kan inte ändras.' => 'This booking can no longer be rescheduled.',
        'Aktiva ärenden kan inte anonymiseras.' => 'Only closed bookings can be anonymised.',
        'Bokningen saknas.' => 'Booking not found.',
        'Ogiltig inställning.' => 'Check the setting value.',
        'Ogiltig regeländring.' => 'Check the rule values.',
        'Ogiltig regel.' => 'Check the rule type.',
        'Regeln saknas.' => 'Rule not found.',
        'Regeln saknas eller är oförändrad.' => 'Rule not found or unchanged.',
        'Procenten är för hög.' => 'The percentage exceeds 100%.',
        'Blockeringen saknas.' => 'The block was not found.',
        'Ogiltigt datum.' => 'Choose a valid date.',
        'Ogiltig betalstatus.' => 'Choose a valid payment status.',
        'Ogiltig intern notering.' => 'Use a note of no more than 2000 characters.',
        'Ange namn och giltig e-post.' => 'Enter a name and valid email address.',
        'Ange giltig sluttid för reservation.' => 'Choose a valid reservation expiry.'
    ]);
}
