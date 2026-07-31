<?php

declare(strict_types=1);

use Nfe\Build\Linter;

it('passes syntactically valid PHP', function (): void {
    $files = [
        'Ns/Ok.php' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace Nfe\\Generated\\Ns;\n\nfinal readonly class Ok\n{\n    public function __construct(public string \$name) {}\n}\n",
    ];

    expect(Linter::lint($files))->toBe([]);
});

it('flags a file with a dotted type-hint (the historical bug) as a parse failure', function (): void {
    $files = [
        'ContribuintesV2/Bad.php' => "<?php\n\nnamespace Nfe\\Generated\\ContribuintesV2;\n\nfinal readonly class Bad\n{\n    public function __construct(\n        public ?DFeTech.TaxPayers.Resources.CompanyResourceItem \$company = null,\n    ) {}\n}\n",
        'ContribuintesV2/Good.php' => "<?php\nfinal class Good {}\n",
    ];

    $errors = Linter::lint($files);

    expect($errors)->toHaveCount(1);
    expect($errors[0])->toStartWith('ContribuintesV2/Bad.php: ');
});

it('lists every offending file, not just the first', function (): void {
    $files = [
        'A/One.php' => "<?php class One { public Foo.Bar \$x; }\n",
        'B/Two.php' => "<?php class Two { public Baz.Qux \$y; }\n",
    ];

    expect(Linter::lint($files))->toHaveCount(2);
});
