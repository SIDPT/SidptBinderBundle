<?php


namespace Sidpt\BinderBundle\Command;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\SerializerProvider;

use Claroline\AppBundle\Command\BaseCommandTrait;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Command\AdminCliCommand;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User as UserEntity;
use Claroline\CoreBundle\Security\PlatformRoles;
use Claroline\CoreBundle\Manager\Organization\OrganizationManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


// entities
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Resource\ResourceType;
use Claroline\CoreBundle\Entity\Resource\Directory;

use Sidpt\BinderBundle\Entity\Binder;
use Sidpt\BinderBundle\Entity\Document;

/**
 * Create the course hierarchy for the SIDPT project
 */
class SidptDataLoadCommand extends Command
{

    private $om;
    private $crud;
    private $serializer;
    private $organizationManager;



    public function __construct(
        ObjectManager $om,
        Crud $crud,
        SerializerProvider $serializer,
        OrganizationManager $organizationManager
    ) {
        $this->om = $om;
        $this->crud = $crud;
        $this->serializer = $serializer;
        $this->organizationManager = $organizationManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate the courses hierarchy for the SIDPT project')
            ->addArgument('csv_path', InputArgument::REQUIRED, 'The absolute path to the csv file containing a list of curriculum/course/module/learning units to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        // get emails from file
        $file = $input->getArgument('csv_path');
        $lines = str_getcsv(file_get_contents($file), PHP_EOL);

        $workspaceRepo = $this->om->getRepository(Workspace::class);
        $resourceNodeRepo = $this->om->getRepository(ResourceNode::class);
        $binderRepo = $this->om->getRepository(Binder::class);
        $documentRepo = $this->om->getRepository(Document::class);
        $typesRepo = $this->om->getRepository(ResourceType::class);

        $binderType = $typesRepo->findOneBy([
            'name' => 'sidpt_binder'
        ]);
        $documentType = $typesRepo->findOneBy([
            'name' => 'sidpt_document'
        ]);

        $directoryType = $typesRepo->findOneBy([
            'name' => 'directory'
        ]);

        $user = $this->om->getRepository(UserEntity::class)->findOneBy(['id'=>1]);

        foreach ($lines as $key => $line) {
            $fields = str_getcsv($line, ';');
            $curriculum = trim($fields[0]);
            $course = trim($fields[1]);
            $module = trim($fields[2]);
            $learningUnit = trim($fields[3]);
            
            // Check if curriculum exist :
            // Curriculum is alledgedly a workspace
            $workspace = $workspaceRepo->findOneBy(['name' => $curriculum]);
            // Check if course exist in the curriculum
            // course is a binder resource
            if (empty($workspace)) {
                $workspaceCode = str_replace(" ", "_", strtolower($curriculum));
                $output->writeln("Making workspace {$curriculum} with code ${workspaceCode}");
                $workspace = new Workspace();
                
                $workspace->addOrganization($this->organizationManager->getDefault());
                $workspace->setCreator($user);

                $data = [
                    "name" => $curriculum,
                    "code" => $workspaceCode,
                    "meta" => [
                      "model" => false,
                      "personal" => false,
                      "usedStorage" => 0,
                      "totalUsers" => 0,
                      "totalResources" => 0,
                      "forceLang" => false
                    ],
                    "roles" => [],
                    "opening" => [
                      "type" => 'tool',
                      "target" => 'home'
                    ],
                    "display" => [
                      "showProgression" => true,
                      "showMenu" => true
                    ],
                    "breadcrumb" => [
                      "displayed" => true,
                      "items" => ['desktop', 'workspaces', 'current', 'tool']
                    ],
                    "registration" => [
                      "validation" => false,
                      "selfRegistration" => true,
                      "selfUnregistration" => true
                    ],
                    "restrictions" => [
                      "hidden" => false,
                      "dates" => []
                    ],
                    "notifications" => [
                      "enabled" => false
                    ]
                ];
                $this->serializer->deserialize($data, $workspace);

                $this->om->persist($workspace);
                $this->om->flush();
            }
            $curriculumNode = $resourceNodeRepo->findOneBy([
                'name' => $curriculum,
                'workspace' => $workspace->getId(),
                'resourceType' => $directoryType->getId()
            ]);
            if (empty($curriculumNode)) {
                $output->writeln("Making node and directory {$curriculum}");
                $curriculumNode = new ResourceNode();
                $curriculumNode->setName($curriculum);
                $curriculumNode->setWorkspace($workspace);
                $curriculumNode->setResourceType($directoryType);
                $curriculumNode->setCreator($user);
                $curriculumNode->setMimeType("custom/directory");

                $curriculumDirectory = new Directory();
                $curriculumDirectory->setResourceNode($curriculumNode);
                $curriculumDirectory->setName($curriculum);

                $this->om->persist($curriculumNode);
                $this->om->persist($curriculumDirectory);
                $this->om->flush();
            }


            $courseNode = $resourceNodeRepo->findOneBy([
                'name' => $course,
                'parent'=> $curriculumNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $binderType->getId()
            ]);
            if (empty($courseNode)) {
                $output->writeln("Making node and binder {$curriculum}/{$course}");
                $courseNode = new ResourceNode();
                $courseNode->setName($course);
                $courseNode->setWorkspace($workspace);
                $courseNode->setResourceType($binderType);
                $courseNode->setParent($curriculumNode);
                $courseNode->setCreator($user);
                $courseNode->setMimeType("custom/sidpt_binder");

                $courseBinder = new Binder();
                $courseBinder->setResourceNode($courseNode);
                $courseBinder->setName($course);
                

                $this->om->persist($courseNode);
                $this->om->persist($courseBinder);
                $this->om->flush();

            }

            // Check if module exist in the course
            // module is also a binder
            
            $moduleNode = $resourceNodeRepo->findOneBy([
                'name' => $module,
                'parent'=>$courseNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $binderType->getId()
            ]);
            if (empty($moduleNode)) {
                $output->writeln("Making node and binder {$curriculum}/{$course}/{$module}");
                $moduleNode = new ResourceNode();
                $moduleNode->setName($module);
                $moduleNode->setWorkspace($workspace);
                $moduleNode->setResourceType($binderType);
                $moduleNode->setParent($courseNode);
                $moduleNode->setCreator($user);
                $moduleNode->setMimeType("custom/sidpt_binder");

                $moduleBinder = new Binder();
                $moduleBinder->setResourceNode($moduleNode);
                $moduleBinder->setName($module);
                

                $this->om->persist($moduleNode);
                $this->om->persist($moduleBinder);
                $this->om->flush();
            }
            // Check if learning unit exist within the module
            // a learning unit is a document
            $learningUnitNode = $resourceNodeRepo->findOneBy([
                'name' => $learningUnit,
                'parent'=>$moduleNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $documentType->getId()
            ]);
            if (empty($learningUnitNode)) {
                $output->writeln("Making node and document {$curriculum}/{$course}/{$module}/{$learningUnit}");
                $learningUnitNode = new ResourceNode();
                $learningUnitNode->setName($learningUnit);
                $learningUnitNode->setWorkspace($workspace);
                $learningUnitNode->setResourceType($binderType);
                $learningUnitNode->setParent($moduleNode);
                $learningUnitNode->setCreator($user);
                $learningUnitNode->setMimeType("custom/sidpt_document");

                $learningUnitDocument = new Document();
                $learningUnitDocument->setResourceNode($learningUnitNode);
                $learningUnitDocument->setName($learningUnit);

                $this->om->persist($learningUnitNode);
                $this->om->persist($learningUnitDocument);
                $this->om->flush();
            }
        }


        return 0;
    }
}
