<?php

it('boots and responds', function () {
    $this->get('/')->assertOk();
});
