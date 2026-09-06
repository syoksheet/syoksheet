<?php

use App\Enums\ErrorCode;
use App\Enums\RateLimitCode;

/**
 * The enum and the published API contract are two copies of the same list. Nothing
 * forces them to agree. A code missing from the contract still works in PHP, and a code
 * missing from the enum still documents fine, so only a test catches the difference.
 */
it('matches the published openapi contract exactly', function () {
    /** @var array{components: array{schemas: array{BusinessRuleError: array{properties: array{code: array{enum: list<string>}}}}}} $spec */
    $spec = json_decode((string) file_get_contents(base_path('docs/api/openapi.json')), associative: true);

    $published = $spec['components']['schemas']['BusinessRuleError']['properties']['code']['enum'];
    $codes = array_column(ErrorCode::cases(), 'value');

    sort($published);
    sort($codes);

    expect($codes)->toBe($published);
});

/**
 * The validation catalog is where a developer looks a code up. A code that is missing
 * from it is undocumented, however correct the API spec happens to be.
 */
/**
 * Read the codes out of one section of the catalog.
 *
 * Each table gets its own section, and this only matches the first cell of a row.
 * Searching the whole document would mix the two catalogs together and would also find
 * sso_required, which is a 403 and belongs to neither.
 *
 * @return list<string>
 */
function documentedCodesUnder(string $heading): array
{
    if ($heading === '') {
        return [];
    }

    $catalog = (string) file_get_contents(base_path('docs/validation.md'));

    // Split on the heading line rather than the words, so a prose mention of the same
    // phrase cannot select the wrong table. The section ends at the next heading of any
    // depth, so a subsection's table is never swallowed into this one.
    $afterHeading = preg_split('/^#{2,}\s.*'.preg_quote($heading, '/').'.*$/m', $catalog) ?: [];
    $section = (preg_split('/^#{2,}\s/m', $afterHeading[1] ?? '') ?: [''])[0];

    preg_match_all('/^\|\s*`([a-z0-9_]+)`(?:\s*\/\s*`([a-z0-9_]+)`)?\s*\|/m', $section, $rows);

    return array_values(array_filter(array_merge($rows[1], $rows[2])));
}

it('documents exactly the business rule codes the catalog lists', function () {
    $documented = documentedCodesUnder('Business-Rule Error Codes');
    $codes = array_column(ErrorCode::cases(), 'value');

    sort($documented);
    sort($codes);

    expect($codes)->toBe($documented);
});

it('documents exactly the rate limit codes the catalog lists', function () {
    $documented = documentedCodesUnder('Abuse Rate Limits');
    $codes = array_column(RateLimitCode::cases(), 'value');

    sort($documented);
    sort($codes);

    expect($codes)->toBe($documented);
});

/**
 * A code is worse than useless when it names a limit no tier actually imposes, because
 * it invites someone to write a controller that enforces a cap the product does not
 * have.
 *
 * Members, teams and the verification queue are unlimited on every tier, and a
 * work-domain address is allowed as someone's primary email.
 */
it('has no code for a limit the product does not impose', function (string $removed) {
    expect(array_column(ErrorCode::cases(), 'value'))->not->toContain($removed);
})->with([
    'member_limit_reached',
    'team_limit_reached',
    'queue_capacity_reached',
    'work_domain_primary',
    'brag_limit_reached',
]);

/**
 * A rate limit tells the caller to slow down, which is a 429. It is not a 422, where the
 * request was valid and the system's state refused it. Keeping the two catalogs apart is
 * what stops a rate limit being raised with the wrong status.
 */
it('keeps rate limit codes out of the business rule catalog', function () {
    $businessRules = array_column(ErrorCode::cases(), 'value');

    foreach (array_column(RateLimitCode::cases(), 'value') as $rateLimit) {
        expect($businessRules)->not->toContain($rateLimit);
    }
});

/**
 * The rate limit codes are published in the same spec and drift the same way.
 */
it('matches the published rate limit contract exactly', function () {
    /** @var array{components: array{schemas: array{RateLimitError: array{properties: array{code: array{enum: list<string>}}}}}} $spec */
    $spec = json_decode((string) file_get_contents(base_path('docs/api/openapi.json')), associative: true);

    $published = $spec['components']['schemas']['RateLimitError']['properties']['code']['enum'];
    $codes = array_column(RateLimitCode::cases(), 'value');

    sort($published);
    sort($codes);

    expect($codes)->toBe($published);
});
