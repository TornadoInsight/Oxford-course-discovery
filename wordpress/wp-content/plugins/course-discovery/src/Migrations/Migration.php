<?php

declare(strict_types=1);

namespace CourseDiscovery\Migrations;

interface Migration
{
    /** Stable, never-reused identifier — used as the "applied" marker. */
    public function version(): string;

    public function up(): void;
}
