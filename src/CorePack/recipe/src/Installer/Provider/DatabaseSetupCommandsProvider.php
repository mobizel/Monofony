<?php



declare(strict_types=1);

namespace App\Installer\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DatabaseSetupCommandsProvider implements DatabaseSetupCommandsProviderInterface
{
    /** @var ManagerRegistry */
    private $doctrineRegistry;

    public function __construct(ManagerRegistry $doctrineRegistry)
    {
        $this->doctrineRegistry = $doctrineRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper): array
    {
        if (!$this->isDatabasePresent()) {
            return [
                'doctrine:database:create',
                'doctrine:migrations:migrate' => ['--no-interaction' => true],
            ];
        }

        return array_merge($this->setupDatabase($input, $output, $questionHelper), [
            'doctrine:migrations:version' => [
                '--add' => true,
                '--all' => true,
                '--no-interaction' => true,
            ],
        ]);
    }

    /**
     * @throws \Exception
     */
    private function isDatabasePresent(): bool
    {
        $databaseName = $this->getDatabaseName();

        try {
            $schemaManager = $this->getSchemaManager();

            return in_array($databaseName, $schemaManager->listDatabases());
        } catch (\Exception $exception) {
            $message = $exception->getMessage();

            $mysqlDatabaseError = false !== strpos($message, sprintf("Unknown database '%s'", $databaseName));
            $postgresDatabaseError = false !== strpos($message, sprintf('database "%s" does not exist', $databaseName));

            if ($mysqlDatabaseError || $postgresDatabaseError) {
                return false;
            }

            throw $exception;
        }
    }

    private function setupDatabase(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper): array
    {
        $outputStyle = new SymfonyStyle($input, $output);
        $outputStyle->writeln('It appears that your database already exists.');
        $outputStyle->writeln('<error>Warning! This action will erase your database.</error>');

        $question = new ConfirmationQuestion('Would you like to reset it? (y/N) ', false);
        if ($questionHelper->ask($input, $output, $question)) {
            return [
                'doctrine:database:drop' => ['--force' => true],
                'doctrine:database:create',
                'doctrine:migrations:migrate' => ['--no-interaction' => true],
            ];
        }

        if (!$this->isSchemaPresent()) {
            return ['doctrine:migrations:migrate' => ['--no-interaction' => true]];
        }

        $outputStyle->writeln('Seems like your database contains schema.');
        $outputStyle->writeln('<error>Warning! This action will erase your database.</error>');
        $question = new ConfirmationQuestion('Do you want to reset it? (y/N) ', false);
        if ($questionHelper->ask($input, $output, $question)) {
            return [
                'doctrine:schema:drop' => ['--force' => true],
                'doctrine:migrations:migrate' => ['--no-interaction' => true],
            ];
        }

        return [];
    }

    private function isSchemaPresent(): bool
    {
        return 0 !== count($this->getSchemaManager()->listTableNames());
    }

    private function getDatabaseName(): string
    {
        return (string) $this->getEntityManager()->getConnection()->getDatabase();
    }

    private function getSchemaManager(): AbstractSchemaManager
    {
        return $this->getEntityManager()->getConnection()->getSchemaManager();
    }

    /**
     * @return EntityManagerInterface|ObjectManager
     */
    private function getEntityManager(): ObjectManager
    {
        return $this->doctrineRegistry->getManager();
    }
}
