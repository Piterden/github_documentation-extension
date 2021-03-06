<?php namespace Anomaly\GithubDocumentationExtension\Command;

use Anomaly\ConfigurationModule\Configuration\Contract\ConfigurationRepositoryInterface;
use Anomaly\DocumentationModule\Project\Contract\ProjectInterface;
use Anomaly\EncryptedFieldType\EncryptedFieldTypePresenter;
use Github\Client;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * Class GetContent
 *
 * @link          http://pyrocms.com/
 * @author        PyroCMS, Inc. <support@pyrocms.com>
 * @author        Ryan Thompson <ryan@pyrocms.com>
 * @package       Anomaly\GithubDocumentationExtension\Command
 */
class GetContent implements SelfHandling
{

    use DispatchesJobs;

    /**
     * The project instance.
     *
     * @var ProjectInterface
     */
    protected $project;

    /**
     * The project reference.
     *
     * @var string
     */
    protected $reference;

    /**
     * The documentation page.
     *
     * @var string
     */
    protected $page;

    /**
     * Create a new GetContent instance.
     *
     * @param ProjectInterface $project
     * @param string           $reference
     * @param string           $page
     */
    public function __construct(ProjectInterface $project, $reference, $page)
    {
        $this->project   = $project;
        $this->reference = $reference;
        $this->page      = $page;
    }

    /**
     * Handle the command.
     *
     * @param Repository                       $config
     * @param ConfigurationRepositoryInterface $configuration
     * @return string
     */
    public function handle(Repository $config, ConfigurationRepositoryInterface $configuration)
    {
        $namespace = 'anomaly.extension.github_documentation';

        /* @var EncryptedFieldTypePresenter $token */
        $username   = $configuration->value($namespace . '::username', $this->project->getSlug());
        $repository = $configuration->value($namespace . '::repository', $this->project->getSlug());
        $token      = $configuration->presenter($namespace . '::token', $this->project->getSlug());

        // Decrypt the value.
        $token = $token->decrypt();

        $client = new Client();

        $client->authenticate($token, null, 'http_token');

        $path = 'docs/' . $config->get('app.locale') . '/' . $this->page . '.md';

        if (!$client->repos()->contents()->exists($username, $repository, $path, $this->reference)) {
            $path = 'docs/' . $config->get('app.fallback_locale') . '/' . $this->page . '.md';
        }

        return base64_decode(
            array_get(
                $client
                    ->repos()
                    ->contents()
                    ->show(
                        $username,
                        $repository,
                        $path,
                        $this->reference
                    ),
                'content'
            )
        );
    }
}
