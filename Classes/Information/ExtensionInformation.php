<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Information;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionInformation
{
    protected const SYSTEM_COLUMNS = [
        'endtime',
        'starttime',
        'hidden',
        'sys_language_uid',
        'l10n_parent',
        'l10n_diffsource',
    ];

    private const CLASSES_PATH = 'Classes/';

    private const CONTROLLER_PATH = 'Classes/Controller/';

    private const MODEL_PATH = 'Classes/Domain/Model/';

    private const CONFIGURATION_PATH = 'Configuration/';

    private const SITE_SET_PATH = 'Configuration/Sets/';

    private const TCA_PATH = 'Configuration/TCA/';

    private const TCA_OVERRIDES_PATH = 'Configuration/TCA/Overrides/';

    private const TYPOSCRIPT_DEFAULT_PATH = 'Configuration/TypoScript/';

    public function __construct(
        private string $extensionKey,
        private string $composerPackageName,
        private readonly string $title,
        private readonly string $description,
        private readonly string $version,
        private readonly string $category,
        private readonly string $state,
        private readonly string $author,
        private readonly string $authorEmail,
        private readonly string $authorCompany,
        private readonly string $namespaceForAutoload,
        private string $extensionPath,
        private readonly CreatorInformation $creatorInformation = new CreatorInformation()
    ) {}

    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }

    public function getExtensionName(): string
    {
        return GeneralUtility::underscoredToUpperCamelCase($this->extensionKey);
    }

    public function getComposerPackageName(): string
    {
        return $this->composerPackageName;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function getAuthorCompany(): string
    {
        return $this->authorCompany;
    }

    public function getNamespaceForAutoload(): string
    {
        // In general "\\\\" is correct as this would result in MyVendor\\MyExt\\ normally.
        // BUT: As we are using JSON_UNESCAPED_SLASHES while building the composer.json file
        // the "\\\\" will be applied as it is. So, we have to remove the escaping here.
        return str_replace('\\\\', '\\', $this->namespaceForAutoload);
    }

    /**
     * Return a namespace prefix which you can use within your extension classes for "namespace XY"
     */
    public function getNamespacePrefix(): string
    {
        return $this->getNamespaceForAutoload();
    }

    public function getTableNamePrefix(): string
    {
        return sprintf(
            'tx_%s_domain_model_',
            str_replace('_', '', $this->getExtensionKey()),
        );
    }

    public function getExtensionPath(): string
    {
        return $this->extensionPath;
    }

    public function getClassesPath(): string
    {
        return $this->extensionPath . self::CLASSES_PATH;
    }

    public function getConfigurationPath(): string
    {
        return $this->getExtensionPath() . self::CONFIGURATION_PATH;
    }

    public function getControllerPath(): string
    {
        return $this->getExtensionPath() . self::CONTROLLER_PATH;
    }

    public function getModelPath(): string
    {
        return $this->getExtensionPath() . self::MODEL_PATH;
    }

    public function getSetPath(): string
    {
        return $this->getExtensionPath() . self::SITE_SET_PATH;
    }

    public function getDefaultTypoScriptPath(): string
    {
        return $this->getExtensionPath() . self::TYPOSCRIPT_DEFAULT_PATH;
    }

    public function getTcaPath(): string
    {
        return $this->getExtensionPath() . self::TCA_PATH;
    }

    public function getTcaOverridesPath(): string
    {
        return $this->getExtensionPath() . self::TCA_OVERRIDES_PATH;
    }

    public function getFilePathForTcaTable(string $tcaTableName): string
    {
        return $this->getTcaPath() . $tcaTableName . '.php';
    }

    public function getFilePathForController(string $classname): string
    {
        return $this->getControllerPath() . $classname . '.php';
    }

    public function getFilePathForModel(string $classname): string
    {
        return $this->getModelPath() . $classname . '.php';
    }

    public function getControllerClassnames(): array
    {
        $controllerPath = $this->getControllerPath();
        if (!is_dir($controllerPath)) {
            return [];
        }

        $controllerClasses = [];
        foreach (scandir($controllerPath) as $file) {
            if ($file === '.') {
                continue;
            }
            if ($file === '..') {
                continue;
            }
            // Only include files that end with "Controller.php"
            if (!str_ends_with($file, 'Controller.php')) {
                continue;
            }

            $className = pathinfo($file, PATHINFO_FILENAME);

            // Must be a valid PHP class name and not start with "_"
            if (preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $className)) {
                $controllerClasses[] = $className;
            }
        }

        sort($controllerClasses);

        return $controllerClasses;
    }

    public function getModelClassnames(): array
    {
        $modelPath = $this->getModelPath();
        if (!is_dir($modelPath)) {
            return [];
        }

        $modelClasses = [];
        foreach (scandir($modelPath) as $file) {
            if ($file === '.') {
                continue;
            }
            if ($file === '..') {
                continue;
            }
            $modelClasses[] = pathinfo($file, PATHINFO_FILENAME);
        }

        sort($modelClasses);

        return $modelClasses;
    }

    public function getExtbaseControllerClassnames(): array
    {
        $nodeFinder = new NodeFinder();
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $extbaseControllerClassnames = [];
        foreach ($this->getControllerClassnames() as $controllerClassname) {
            $stmts = $parser->parse(file_get_contents($this->getFilePathForController($controllerClassname)));
            $classNode = $nodeFinder->findFirst($stmts, static fn(Node $node): bool => $node instanceof Class_
                && $node->extends instanceof Name
                && $node->extends->toString() === 'ActionController');

            if ($classNode instanceof Class_) {
                $extbaseControllerClassnames[] = $classNode->name->name;
            }
        }

        sort($extbaseControllerClassnames);

        return $extbaseControllerClassnames;
    }

    public function getExtbaseModelClassnames(): array
    {
        $nodeFinder = new NodeFinder();
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $extbaseModelClassnames = [];
        foreach ($this->getModelClassnames() as $modelClassname) {
            $stmts = $parser->parse(file_get_contents($this->getFilePathForModel($modelClassname)));
            $classNode = $nodeFinder->findFirst($stmts, static fn(Node $node): bool => $node instanceof Class_
                && $node->extends instanceof Name
                && $node->extends->toString() === 'AbstractEntity');

            if ($classNode instanceof Class_) {
                $extbaseModelClassnames[] = $classNode->name->name;
            }
        }

        sort($extbaseModelClassnames);

        return $extbaseModelClassnames;
    }

    public function getExtbaseControllerActionNames(string $extbaseControllerClassname): array
    {
        $nodeFinder = new NodeFinder();
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $extbaseControllerActionNames = [];

        $stmts = $parser->parse(file_get_contents($this->getFilePathForController($extbaseControllerClassname)));
        $classMethodNodes = $nodeFinder->find($stmts, static fn(Node $node): bool => $node instanceof ClassMethod
            && $node->isPublic()
            && str_ends_with($node->name->name, 'Action'));

        foreach ($classMethodNodes as $classMethodNode) {
            if ($classMethodNode instanceof ClassMethod) {
                $extbaseControllerActionNames[] = $classMethodNode->name->name;
            }
        }

        sort($extbaseControllerActionNames);

        return $extbaseControllerActionNames;
    }

    /**
     * @return string[] All directories in folder Configuration/Sets/ that contain a config.yaml
     */
    public function getSets(): array
    {
        $setPath = $this->getSetPath();

        if (!is_dir($setPath)) {
            return [];
        }

        $sets = [];

        foreach (scandir($setPath) as $entry) {
            if ($entry === '.') {
                continue;
            }
            if ($entry === '..') {
                continue;
            }
            $fullDirPath = $setPath . DIRECTORY_SEPARATOR . $entry;
            $configFilePath = $fullDirPath . DIRECTORY_SEPARATOR . 'config.yaml';

            if (is_dir($fullDirPath) && is_file($configFilePath)) {
                $sets[] = $entry;
            }
        }

        sort($sets);

        return $sets;
    }

    public function getConfiguredTcaTables(): array
    {
        $configuredTcaTables = [];
        $tcaPath = $this->getTcaPath();
        if (!is_dir($tcaPath)) {
            return $configuredTcaTables;
        }

        foreach (scandir($tcaPath) as $file) {
            if ($file === '.') {
                continue;
            }
            if ($file === '..') {
                continue;
            }
            $filePath = $tcaPath . '/' . $file;

            if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
                $configuredTcaTables[] = pathinfo($filePath, PATHINFO_FILENAME);
            }
        }

        sort($configuredTcaTables);

        return $configuredTcaTables;
    }

    public function getTcaForTable(string $tableName): array
    {
        $tcaTableFilePath = $this->getFilePathForTcaTable($tableName);

        // 1) Local TCA file (for this extension)
        if (is_file($tcaTableFilePath)) {
            $tca = require $tcaTableFilePath;
            if (is_array($tca) && $tca !== []) {
                return $tca;
            }
        }

        // 2) Fallback: global TCA (Core / other extensions)
        if (isset($GLOBALS['TCA'][$tableName]) && is_array($GLOBALS['TCA'][$tableName])) {
            return $GLOBALS['TCA'][$tableName];
        }

        return [];
    }

    public function getColumnNamesFromTca(array $tableTca): array
    {
        if (!isset($tableTca['columns']) || !is_array($tableTca['columns'])) {
            return [];
        }

        $columnNames = array_keys($tableTca['columns']);
        sort($columnNames);

        return $columnNames;
    }

    public function getSystemColumnNamesFromTca(array $tableTca): array
    {
        return $this->filterColumns($tableTca, true);
    }

    public function getDomainColumnNamesFromTca(array $tableTca): array
    {
        return $this->filterColumns($tableTca, false);
    }

    private function filterColumns(array $tableTca, bool $systemColumns): array
    {
        $allColumns = $this->getColumnNamesFromTca($tableTca);

        return array_values(array_filter($allColumns, function ($column) use ($systemColumns): bool {
            $isSystem = in_array($column, self::SYSTEM_COLUMNS, true);
            return $systemColumns ? $isSystem : !$isSystem;
        }));
    }

    public function getCreatorInformation(): CreatorInformation
    {
        return $this->creatorInformation;
    }

    public function setExtensionKey(string $extensionKey): void
    {
        $this->extensionKey = $extensionKey;
    }

    public function setComposerPackageName(string $composerPackageName): void
    {
        $this->composerPackageName = $composerPackageName;
    }

    public function setExtensionPath(string $extensionPath): void
    {
        $this->extensionPath = $extensionPath;
    }
}
