<?php

use App\Enums\ErrorCode;

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
it('documents exactly the codes the catalog lists', function () {
    $catalog = (string) file_get_contents(base_path('docs/validation.md'));

    // Only match the first cell of a table row. Searching the whole document would
    // also find sso_required, which is a 403 and deliberately not in this enum.
    preg_match_all('/^\|\s*`([a-z_]+)`(?:\s*\/\s*`([a-z_]+)`)?\s*\|/m', $catalog, $rows);

    $documented = array_values(array_filter(array_merge($rows[1], $rows[2])));
    $codes = array_column(ErrorCode::cases(), 'value');

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
]);
