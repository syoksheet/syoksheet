<?php

use App\Enums\ErrorCode;

/**
 * The enum and the published OpenAPI contract are two copies of one list, and they had
 * already drifted six cases apart before anything raised a single code. Nothing catches
 * that on its own: a code missing from the spec still works in PHP, and a code missing
 * from the enum still documents fine.
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
 * docs/validation.md is where a person looks the code up, so a case absent from it is
 * undocumented however correct the spec is.
 */
it('documents exactly the codes the catalog lists', function () {
    $catalog = (string) file_get_contents(base_path('docs/validation.md'));

    // The first cell of a table row, which in this file is only ever an error code.
    // Matching the whole document would also pick up `sso_required`, which is a 403 and
    // deliberately not in the enum.
    preg_match_all('/^\|\s*`([a-z_]+)`(?:\s*\/\s*`([a-z_]+)`)?\s*\|/m', $catalog, $rows);

    $documented = array_values(array_filter(array_merge($rows[1], $rows[2])));
    $codes = array_column(ErrorCode::cases(), 'value');

    sort($documented);
    sort($codes);

    expect($codes)->toBe($documented);
});

/**
 * A code naming a limit that no tier actually imposes is worse than a missing one: it
 * invites a controller to enforce a cap the product does not have. Members, teams and
 * the verification queue are unlimited on every tier, and a work-domain address is
 * allowed as the primary, so all four of these named a rule that does not exist.
 */
it('has no code for a limit the product does not impose', function (string $removed) {
    expect(array_column(ErrorCode::cases(), 'value'))->not->toContain($removed);
})->with([
    'member_limit_reached',
    'team_limit_reached',
    'queue_capacity_reached',
    'work_domain_primary',
]);
