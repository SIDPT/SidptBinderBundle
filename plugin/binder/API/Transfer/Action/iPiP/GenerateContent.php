<?php

namespace Sidpt\BinderBundle\API\Transfer\Action\iPiP;

use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\API\Options;

use Claroline\AppBundle\API\Transfer\Action\AbstractAction;

use Claroline\AppBundle\Command\BaseCommandTrait;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Command\AdminCliCommand;
use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User as UserEntity;
use Claroline\CoreBundle\Security\PlatformRoles;
use Claroline\CoreBundle\Manager\Organization\OrganizationManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceManager;
use Claroline\TagBundle\Manager\TagManager;

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

use Claroline\CoreBundle\Entity\Widget\Type\ResourceWidget;
use Claroline\CoreBundle\Entity\Widget\Widget;
use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Claroline\CoreBundle\Entity\Widget\WidgetContainerConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;
use Claroline\CoreBundle\Entity\Widget\WidgetInstanceConfig;
use Claroline\CoreBundle\Entity\DataSource;

use Claroline\HomeBundle\Entity\HomeTab;
use Claroline\HomeBundle\Entity\Type\WidgetsTab;

use Claroline\TagBundle\Entity\Tag;
use Claroline\TagBundle\Entity\TaggedObject;

use Icap\LessonBundle\Entity\Lesson;
use Claroline\CoreBundle\Entity\Resource\Text;
use UJM\ExoBundle\Entity\Exercise;
use UJM\ExoBundle\Library\Options\ExerciseType;

use Sidpt\BinderBundle\Entity\Binder;
use Sidpt\BinderBundle\Entity\BinderTab;
use Sidpt\BinderBundle\Entity\Document;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 *
 */
class GenerateContent extends AbstractAction
{

    // Class parameters
    private $om;
    private $crud;
    private $serializer;
    private $finder;
    private $organizationManager;
    private $tagManager;
    private $roleManager;
    private $workspaceManager;
    private $resourceManager;
    private $tokenStorage;


    // Class variables for execute function (hoping it can be help performances)
    private $workspaceRepo;
    private $resourceNodeRepo;
    private $homeTabsRepo;
    private $binderType;
    private $documentType;
    private $directoryType;
    private $lessonType;
    private $exerciseType;
    private $textType;
    private $resourceDataSource;
    private $widgetType;
    private $nodeSeralizer;

    /**
     *
     */
    public function __construct(
        ObjectManager $om,
        Crud $crud,
        SerializerProvider $serializer,
        FinderProvider $finder,
        OrganizationManager $organizationManager,
        TagManager $tagManager,
        RoleManager $roleManager,
        WorkspaceManager $workspaceManager,
        ResourceManager $resourceManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->om = $om;
        $this->crud = $crud;
        $this->serializer = $serializer;
        $this->finder = $finder;
        $this->organizationManager = $organizationManager;
        $this->tagManager = $tagManager;
        $this->roleManager = $roleManager;
        $this->workspaceManager = $workspaceManager;
        $this->resourceManager = $resourceManager;
        $this->tokenStorage = $tokenStorage;

        $this->workspaceRepo = $this->om->getRepository(Workspace::class);
        $this->resourceNodeRepo = $this->om->getRepository(ResourceNode::class);
        $this->homeTabsRepo = $this->om->getRepository(HomeTab::class);
        
        
        $widgetsTypeRepo = $this->om->getRepository(Widget::class);
        $dataSourceRepo = $this->om->getRepository(DataSource::class);
        $typesRepo = $this->om->getRepository(ResourceType::class);

        $this->binderType = $typesRepo->findOneBy(
            [ 'name' => 'sidpt_binder' ]
        );
        $this->documentType = $typesRepo->findOneBy(
            [ 'name' => 'sidpt_document' ]
        );

        $this->directoryType = $typesRepo->findOneBy(
            [ 'name' => 'directory' ]
        );

        $this->lessonType = $typesRepo->findOneBy(
            [ 'name' => 'icap_lesson' ]
        );

        $this->exerciseType = $typesRepo->findOneBy(
            [ 'name' => 'ujm_exercise' ]
        );

        $this->textType = $typesRepo->findOneBy(
            [ 'name' => 'text' ]
        );

        $this->resourceDataSource = $dataSourceRepo->findOneBy(
            ['name' => 'resource']
        );

        $this->widgetType = $widgetsTypeRepo->findOneBy(
            [ 'name' => 'resource' ]
        );
        $this->nodeSeralizer = $this->serializer->get(ResourceNode::class);
    }


    /**
     *
     */
    public function execute(array $data, &$successData = [])
    {
        
        $user = $this->tokenStorage->getToken()->getUser();

        $curriculum = trim($data['curriculum']);
        $course = trim($data['course']);
        $module = trim($data['module']);
        $learningUnit = trim($data['learning_unit']);
            
        ///// WORKSPACE / CURRICULUM
        // for line with empty curriculum, stop right here
        if (empty($curriculum)) {
            $successData['generate_ipip_content'][] = [
                'data' => $data,
                'log' => "Line found with no curriculum provided, passing over",
            ];
            return;
        }
        
        // Check if curriculum exist
        $workspace = $this->workspaceRepo->findOneBy(['name' => $curriculum]);
        // Create it if not
        if (empty($workspace)) {
            $workspaceCode = str_replace(" ", "_", strtolower($curriculum));
            
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
        
            // From workspace manager
            // (seems mandatory to do it this way for default rights)
            $workspace = $this->crud->create(Workspace::class, $data);
            $model = $workspace->getWorkspaceModel();
            $workspace = $this->workspaceManager->copy($model, $workspace, false);
            $workspace = $this->serializer->get(Workspace::class)
                ->deserialize($data, $workspace);

            $workspace->setCreator($user);
            $this->om->persist($workspace);

            $this->om->flush();
        }


        // Tag the workspace (if not already done)
        $this->tagManager->tagObject(
            ['Curriculum',$curriculum],
            $workspace
        );
        
        // Workspace root directory
        $curriculumNode = $this->resourceNodeRepo->findOneBy(
            [
                'parent' => null,
                'workspace' => $workspace->getId()
            ]
        );
        $curriculumNodeData = $this->nodeSeralizer->serialize($curriculumNode);
        
        $wscollaborator = $this->roleManager->getCollaboratorRole($workspace);
        foreach ($curriculumNodeData["rights"] as $key => $right) {
            // if we want to allow everyone to open the content
            // I would not recommend it as it would rather depends on each resources status
            // $curriculumNodeData["rights"][$key]['permissions']['open'] = true;
            if ($right['name'] == $wscollaborator->getName()) {
                $curriculumNodeData["rights"][$key]['permissions']['edit'] = true;
            }
        }
        // Update root node rights
        $this->nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $curriculumNode);


        $curriculumSummaryNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "Summary",
                'parent' => $curriculumNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->documentType->getId(),
            ]
        );
        if (empty($curriculumSummaryNode)) {
            $curriculumSummaryNode = new ResourceNode();
            $curriculumSummaryNode->setName("Summary");
            $curriculumSummaryNode->setWorkspace($workspace);
            $curriculumSummaryNode->setResourceType($this->documentType);
            $curriculumSummaryNode->setCreator($user);
            $curriculumSummaryNode->setParent($curriculumNode);
            $curriculumSummaryNode->setMimeType("custom/sidpt_document");

            $curriculumSummary = new Document();
            $curriculumSummary->setResourceNode($curriculumSummaryNode);
            $curriculumSummary->setName("Summary");

            $this->om->persist($curriculumSummaryNode);
            $this->om->persist($curriculumSummary);

            // Add the summary as first tab of the workspace home
            $summaryTab = [
                'title' => "Summary",
                'longTitle' => "Curriculum summary",
                'slug' => 'summary',
                'context' => HomeTab::TYPE_WORKSPACE,
                'workspace' => [
                    'id' => $workspace->getUuid()
                ],
                'type' => WidgetsTab::getType(),
                'class' => WidgetsTab::class,
                'position' => 1,
                'restrictions' => [
                    'hidden' => false,
                ],
                'parameters' => [
                    'widgets' => [[
                        'name' => null,
                        'visible' => true,
                        'display' => [
                            'layout' => [1],
                            'color' => '#333333',
                            'backgroundType' => 'color',
                            'background' => '#ffffff',
                        ],
                        'parameters' => [],
                        'contents' => [[
                            'type' => 'resource',
                            'source' => 'resource',
                            "parameters" => [
                                "showResourceHeader" => false,
                                "resource" => [
                                    'id' => $curriculumSummaryNode->getUuid()
                                ]
                            ],
                        ]],
                    ]],
                ],
            ];
            $tab = $this->serializer->deserialize($summaryTab, new HomeTab());
            $this->om->persist($tab);
            $this->om->flush();

            $successData['generate_ipip_content'][] = [
                'data' => $data,
                'log' => "Created the summary document of {$curriculum}",
            ];
        }
        
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $curriculumSummaryNode
        );

        if (empty($course)) { // No course provided, ending the treatment
            return;
        }

        $courseNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => $course,
                'parent'=> $curriculumNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->binderType->getId()
            ]
        );
        if (empty($courseNode)) {
            $courseNode = new ResourceNode();
            $courseNode->setName($course);
            $courseNode->setWorkspace($workspace);
            $courseNode->setResourceType($this->binderType);
            $courseNode->setParent($curriculumNode);
            $courseNode->setCreator($user);
            $courseNode->setMimeType("custom/sidpt_binder");


            $courseBinder = new Binder();
            $courseBinder->setResourceNode($courseNode);
            $courseBinder->setName($course);
            $this->om->persist($courseNode);
            $this->om->persist($courseBinder);
            
            $this->tagManager->tagData(
                ['Course',$curriculum, $course],
                [ 0 => [
                    'id'=> $courseNode->getUuid(),
                    'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
                    'name' => "{$curriculum}|{$course}"
                ]]
            );


            $tabs = $this->homeTabsRepo->findBy(
                [
                    'workspace' => $workspace->getUuid(),
                    'parent' => null
                ]
            );

            $newPosition = count($tabs) + 1;
            // Add a new tab the curriculum workspace, and add the course node to it
            $binderTab = [
                'title' => $courseNode->getName(),
                'longTitle' => $courseNode->getName(),
                'slug' => $courseNode->getSlug(),
                'context' => HomeTab::TYPE_WORKSPACE,
                'workspace' => [
                    'id' => $workspace->getUuid()
                ],
                'type' => WidgetsTab::getType(),
                'class' => WidgetsTab::class,
                'position' => $newPosition,
                'restrictions' => [
                    'hidden' => false,
                ],
                'parameters' => [
                    'widgets' => [[
                        'name' => null,
                        'visible' => true,
                        'display' => [
                            'layout' => [1],
                            'color' => '#333333',
                            'backgroundType' => 'color',
                            'background' => '#ffffff',
                        ],
                        'parameters' => [],
                        'contents' => [[
                            'type' => 'resource',
                            'source' => 'resource',
                            "parameters" => [
                                "showResourceHeader" => false,
                                "resource" => [
                                    'id' => $courseNode->getUuid()
                                ]
                            ],
                        ]],
                    ]],
                ],
            ];
            $tab = $this->serializer->deserialize($binderTab, new HomeTab());
            $this->om->persist($tab);
            $this->om->flush();
        } else {
            $courseBinder = $this->resourceManager->getResourceFromNode($courseNode);
        }
        $this->nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $courseNode);


        $courseSummaryNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "Summary",
                'parent'=> $courseNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->documentType->getId()
            ]
        );
        if (empty($courseSummaryNode)) {
            $courseSummaryNode = new ResourceNode();
            $courseSummaryNode->setName("Summary");
            $courseSummaryNode->setWorkspace($workspace);
            $courseSummaryNode->setResourceType($this->documentType);
            $courseSummaryNode->setCreator($user);
            $courseSummaryNode->setParent($courseNode);
            $courseSummaryNode->setMimeType("custom/sidpt_document");
            $this->om->persist($courseSummaryNode);

            $courseSummary = new Document();
            $courseSummary->setResourceNode($courseSummaryNode);
            $courseSummary->setName("Summary");
            $this->om->persist($courseSummary);

            // Add the summary as first tab of the course binder
            $courseSummaryTab = new BinderTab();
            $courseSummaryTab->setDocument($courseSummary);
            $courseSummaryTab->setOwner($courseBinder);
            $courseBinder->addBinderTab($courseSummaryTab);
            
            $this->om->persist($courseSummaryTab);
            $this->om->persist($courseBinder);
            $this->om->flush();
        }
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $courseSummaryNode
        );

        // No module provided, ending the treatment
        if (empty($module)) {
            return;
        }
        // Check if module exist in the course
        // module is also a binder
        
        $moduleNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => $module,
                'parent'=>$courseNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->binderType->getId()
            ]
        );
        if (empty($moduleNode)) {
            $moduleNode = new ResourceNode();
            $moduleNode->setName($module);
            $moduleNode->setWorkspace($workspace);
            $moduleNode->setResourceType($this->binderType);
            $moduleNode->setParent($courseNode);
            $moduleNode->setCreator($user);
            $moduleNode->setMimeType("custom/sidpt_binder");

            $moduleBinder = new Binder();
            $moduleBinder->setResourceNode($moduleNode);
            $moduleBinder->setName($module);

            $this->om->persist($moduleNode);
            $this->om->persist($moduleBinder);
            
            $this->tagManager->tagData(
                ['Module', $curriculum, $course, $module],
                [ 0 => [
                    'id'=> $moduleNode->getUuid(),
                    'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
                    'name' => "{$curriculum}|{$course}|{$module}"
                ]]
            );

            // Add module to the course binder
            $moduleTab = new BinderTab();
            $moduleTab->setBinder($moduleBinder);
            $moduleTab->setOwner($courseBinder);
            $courseBinder->addBinderTab($moduleTab);
            
            $this->om->persist($moduleTab);
            $this->om->persist($courseBinder);
            $this->om->flush();
        } else {
            $moduleBinder = $this->resourceManager->getResourceFromNode($moduleNode);
        }
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $moduleNode
        );

        $moduleSummaryNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "Summary",
                'parent'=> $moduleNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->documentType->getId()
            ]
        );
        if (empty($moduleSummaryNode)) {
            $moduleSummaryNode = new ResourceNode();
            $moduleSummaryNode->setName("Summary");
            $moduleSummaryNode->setWorkspace($workspace);
            $moduleSummaryNode->setResourceType($this->documentType);
            $moduleSummaryNode->setCreator($user);
            $moduleSummaryNode->setParent($moduleNode);
            $moduleSummaryNode->setMimeType("custom/sidpt_document");

            $moduleSummary = new Document();
            $moduleSummary->setResourceNode($moduleSummaryNode);
            $moduleSummary->setName("Summary");

            $this->om->persist($moduleSummaryNode);
            $this->om->persist($moduleSummary);

            // Add the summary as first tab of the module binder
            $moduleSummaryTab = new BinderTab();
            $moduleSummaryTab->setDocument($moduleSummary);
            $moduleSummaryTab->setOwner($moduleBinder);
            $moduleBinder->addBinderTab($moduleSummaryTab);
            
            $this->om->persist($moduleSummaryTab);
            $this->om->persist($moduleBinder);
            $this->om->flush();
        }
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $moduleSummaryNode
        );

        // No learning unit provided, ending the treatment
        if (empty($learningUnit)) {
            return;
        }

        // Check if learning unit exist within the module
        // a learning unit is a document
        $learningUnitNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => $learningUnit,
                'parent'=>$moduleNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->documentType->getId()
            ]
        );
        if (empty($learningUnitNode)) {
            $learningUnitNode = new ResourceNode();
            $learningUnitNode->setName($learningUnit);
            $learningUnitNode->setWorkspace($workspace);
            $learningUnitNode->setResourceType($this->documentType);
            $learningUnitNode->setParent($moduleNode);
            $learningUnitNode->setCreator($user);
            $learningUnitNode->setMimeType("custom/sidpt_document");

            $learningUnitDocument = new Document();
            $learningUnitDocument->setResourceNode($learningUnitNode);
            $learningUnitDocument->setName($learningUnit);

            $this->om->persist($learningUnitNode);
            $this->om->persist($learningUnitDocument);
            
            $this->tagManager->tagData(
                ['Learning unit',$curriculum, $course, $module, $learningUnit],
                [ 0 => [
                    'id'=> $learningUnitNode->getUuid(),
                    'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
                    'name' => "{$curriculum}|{$course}|{$module}|{$learningUnit}"
                ]]
            );

            $this->om->persist($learningUnitDocument);
            
            // Add the learning unit to the module binder
            $learningUnitTab = new BinderTab();
            $learningUnitTab->setDocument($learningUnitDocument);
            $learningUnitTab->setOwner($moduleBinder);
            $moduleBinder->addBinderTab($learningUnitTab);
            
            $this->om->persist($learningUnitTab);
            $this->om->persist($moduleBinder);
            $this->om->flush();
        } else {
            $learningUnitDocument = $this->resourceManager
                ->getResourceFromNode($learningUnitNode);
        }
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $learningUnitNode
        );

        // for each learning unit, pre-creating the default resources and widget
        // that is :
        // - a description section with 2 column :
        //   - one for a simple text (simple widget or resource)
        //   - and one for a "requirements" directory
        
        // - a practice "exercise":
        $practiceNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "Practice",
                'parent'=>$learningUnitNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->exerciseType->getId()
            ]
        );
        if (empty($practiceNode)) {
            $practiceNode = new ResourceNode();
            $practiceNode->setName("Practice");
            $practiceNode->setWorkspace($workspace);
            $practiceNode->setResourceType($this->exerciseType);
            $practiceNode->setParent($learningUnitNode);
            $practiceNode->setCreator($user);
            $practiceNode->setMimeType("custom/ujm_exercise");
            $practiceExercise = new Exercise();
            $practiceExercise->setResourceNode($practiceNode);
            $practiceExercise->setName("Practice");
            $practiceExercise->setType(ExerciseType::CONCEPTUALIZATION);

            $this->om->persist($practiceNode);
            $this->om->persist($practiceExercise);

            // Preparing widgets for the learning unit document
            $practiceWidget = new ResourceWidget();
            $practiceWidget->setResourceNode($practiceNode);
            $practiceWidget->setShowResourceHeader(true);
            $practiceWidgetInstance = new WidgetInstance();
            $practiceWidgetInstance->setWidget($this->widgetType);
            $practiceWidgetInstance->setDataSource($this->resourceDataSource);
            $practiceWidget->setWidgetInstance($practiceWidgetInstance);
            $practiceWidgetInstanceConfig = new WidgetInstanceConfig();
            $practiceWidgetInstanceConfig->setType("resource");
            $practiceWidgetInstanceConfig->setWidgetInstance(
                $practiceWidgetInstance
            );
            $practiceWidgetContainer = new WidgetContainer();
            $practiceWidgetContainer->addInstance($practiceWidgetInstance);
            $practiceWidgetInstance->setContainer($practiceWidgetContainer);
            $practiceWidgetContainerConfig = new WidgetContainerConfig();
            $practiceWidgetContainerConfig->setBackgroundType("color");
            $practiceWidgetContainerConfig->setBackground("#ffffff");
            $practiceWidgetContainerConfig->setPosition(0);
            $practiceWidgetContainerConfig->setLayout(array(1));
            $practiceWidgetContainerConfig->setWidgetContainer(
                $practiceWidgetContainer
            );
            $this->om->persist($practiceWidget);
            $this->om->persist($practiceWidgetInstance);
            $this->om->persist($practiceWidgetContainer);

            $learningUnitDocument->addWidgetContainer($practiceWidgetContainer);
        }
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $practiceNode
        );

        $theoryNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "Theory",
                'parent'=>$learningUnitNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->lessonType->getId()
            ]
        );
        if (empty($theoryNode)) {
            // - a theory "lesson":
            $theoryNode = new ResourceNode();
            $theoryNode->setName("Theory");
            $theoryNode->setWorkspace($workspace);
            $theoryNode->setResourceType($this->lessonType);
            $theoryNode->setParent($learningUnitNode);
            $theoryNode->setCreator($user);
            $theoryNode->setMimeType("custom/icap_lesson");
            $theoryLesson = new Lesson();
            $theoryLesson->setResourceNode($theoryNode);
            $theoryLesson->setName("Theory");
            $this->om->persist($theoryNode);
            $this->om->persist($theoryLesson);

            $theoryWidget = new ResourceWidget();
            $theoryWidget->setResourceNode($theoryNode);
            $theoryWidget->setShowResourceHeader(true);
            $theoryWidgetInstance = new WidgetInstance();
            $theoryWidgetInstance->setWidget($this->widgetType);
            $theoryWidgetInstance->setDataSource($this->resourceDataSource);
            $theoryWidget->setWidgetInstance($theoryWidgetInstance);
            $theoryWidgetInstanceConfig = new WidgetInstanceConfig();
            $theoryWidgetInstanceConfig->setType("resource");
            $theoryWidgetInstanceConfig->setWidgetInstance($theoryWidgetInstance);
            $theoryWidgetContainer = new WidgetContainer();
            $theoryWidgetContainer->addInstance($theoryWidgetInstance);
            $theoryWidgetInstance->setContainer($theoryWidgetContainer);
            $theoryWidgetContainerConfig = new WidgetContainerConfig();
            $theoryWidgetContainerConfig->setBackgroundType("color");
            $theoryWidgetContainerConfig->setBackground("#ffffff");
            $theoryWidgetContainerConfig->setPosition(0);
            $theoryWidgetContainerConfig->setLayout(array(1));
            $theoryWidgetContainerConfig->setWidgetContainer($theoryWidgetContainer);
            $this->om->persist($theoryWidget);
            $this->om->persist($theoryWidgetInstance);
            $this->om->persist($theoryWidgetContainer);

            $learningUnitDocument->addWidgetContainer($theoryWidgetContainer);
        }
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $theoryNode
        );

        $assessmentNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "Assessment",
                'parent'=>$learningUnitNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->exerciseType->getId()
            ]
        );
        if (empty($assessmentNode)) {
             // - an assessment "Exercise":
            $assessmentNode = new ResourceNode();
            $assessmentNode->setName("Assessment");
            $assessmentNode->setWorkspace($workspace);
            $assessmentNode->setResourceType($this->exerciseType);
            $assessmentNode->setParent($learningUnitNode);
            $assessmentNode->setCreator($user);
            $assessmentNode->setMimeType("custom/ujm_exercise");
            $assessmentExercise = new Exercise();
            $assessmentExercise->setResourceNode($assessmentNode);
            $assessmentExercise->setName("Assessment");
            $assessmentExercise->setType(ExerciseType::SUMMATIVE);
            $this->om->persist($assessmentNode);
            $this->om->persist($assessmentExercise);

            $assessmentWidget = new ResourceWidget();
            $assessmentWidget->setResourceNode($assessmentNode);
            $assessmentWidget->setShowResourceHeader(true);
            $assessmentWidgetInstance = new WidgetInstance();
            $assessmentWidgetInstance->setWidget($this->widgetType);
            $assessmentWidgetInstance->setDataSource($this->resourceDataSource);
            $assessmentWidget->setWidgetInstance($assessmentWidgetInstance);
            $assessmentWidgetInstanceConfig = new WidgetInstanceConfig();
            $assessmentWidgetInstanceConfig->setType("resource");
            $assessmentWidgetInstanceConfig->setWidgetInstance(
                $assessmentWidgetInstance
            );
            $assessmentWidgetContainer = new WidgetContainer();
            $assessmentWidgetContainer->addInstance($assessmentWidgetInstance);
            $assessmentWidgetInstance->setContainer($assessmentWidgetContainer);
            $assessmentWidgetContainerConfig = new WidgetContainerConfig();
            $assessmentWidgetContainerConfig->setBackgroundType("color");
            $assessmentWidgetContainerConfig->setBackground("#ffffff");
            $assessmentWidgetContainerConfig->setPosition(0);
            $assessmentWidgetContainerConfig->setLayout(array(1));
            $assessmentWidgetContainerConfig->setWidgetContainer(
                $assessmentWidgetContainer
            );
            $this->om->persist($assessmentWidget);
            $this->om->persist($assessmentWidgetInstance);
            $this->om->persist($assessmentWidgetContainer);

            $learningUnitDocument->addWidgetContainer($assessmentWidgetContainer);
        }
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $assessmentNode
        );


        $activityNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "Activity",
                'parent'=>$learningUnitNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->textType->getId()
            ]
        );
        if (empty($activityNode)) {
            // - an activity "text":
            $activityNode = new ResourceNode();
            $activityNode->setName("Activity");
            $activityNode->setWorkspace($workspace);
            $activityNode->setResourceType($this->textType);
            $activityNode->setParent($learningUnitNode);
            $activityNode->setCreator($user);
            $activityNode->setMimeType("custom/text");
            $activityText = new Text();
            $activityText->setResourceNode($activityNode);
            $activityText->setName("Activity");
            $this->om->persist($activityNode);
            $this->om->persist($activityText);

            $activityWidget = new ResourceWidget();
            $activityWidget->setResourceNode($activityNode);
            $activityWidget->setShowResourceHeader(true);
            $activityWidgetInstance = new WidgetInstance();
            $activityWidgetInstance->setWidget($this->widgetType);
            $activityWidgetInstance->setDataSource($this->resourceDataSource);
            $activityWidget->setWidgetInstance($activityWidgetInstance);
            $activityWidgetInstanceConfig = new WidgetInstanceConfig();
            $activityWidgetInstanceConfig->setType("resource");
            $activityWidgetInstanceConfig->setWidgetInstance(
                $activityWidgetInstance
            );
            $activityWidgetContainer = new WidgetContainer();
            $activityWidgetContainer->addInstance($activityWidgetInstance);
            $activityWidgetInstance->setContainer($activityWidgetContainer);
            $activityWidgetContainerConfig = new WidgetContainerConfig();
            $activityWidgetContainerConfig->setBackgroundType("color");
            $activityWidgetContainerConfig->setBackground("#ffffff");
            $activityWidgetContainerConfig->setPosition(0);
            $activityWidgetContainerConfig->setLayout(array(1));
            $activityWidgetContainerConfig->setWidgetContainer(
                $activityWidgetContainer
            );
            $this->om->persist($activityWidget);
            $this->om->persist($activityWidgetInstance);
            $this->om->persist($activityWidgetContainer);

            $learningUnitDocument->addWidgetContainer($activityWidgetContainer);
        }
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $activityNode
        );

        $referencesNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "References",
                'parent'=>$learningUnitNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->documentType->getId()
            ]
        );
        if (empty($referencesNode)) {
            // - A references "document"
            $referencesNode = new ResourceNode();
            $referencesNode->setName("References");
            $referencesNode->setWorkspace($workspace);
            $referencesNode->setResourceType($this->documentType);
            $referencesNode->setParent($learningUnitNode);
            $referencesNode->setCreator($user);
            $referencesNode->setMimeType("custom/sidpt_document");
            $referencesDocument = new Document();
            $referencesDocument->setResourceNode($referencesNode);
            $referencesDocument->setName("References");
            $this->om->persist($referencesNode);
            $this->om->persist($referencesDocument);

            $this->nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $referencesNode);

            $referencesWidget = new ResourceWidget();
            $referencesWidget->setResourceNode($referencesNode);
            $referencesWidget->setShowResourceHeader(true);
            $referencesWidgetInstance = new WidgetInstance();
            $referencesWidgetInstance->setWidget($this->widgetType);
            $referencesWidgetInstance->setDataSource($this->resourceDataSource);
            $referencesWidget->setWidgetInstance($referencesWidgetInstance);
            $referencesWidgetInstanceConfig = new WidgetInstanceConfig();
            $referencesWidgetInstanceConfig->setType("resource");
            $referencesWidgetInstanceConfig->setWidgetInstance($referencesWidgetInstance);
            $referencesWidgetContainer = new WidgetContainer();
            $referencesWidgetContainer->addInstance($referencesWidgetInstance);
            $referencesWidgetInstance->setContainer($referencesWidgetContainer);
            $referencesWidgetContainerConfig = new WidgetContainerConfig();
            $referencesWidgetContainerConfig->setBackgroundType("color");
            $referencesWidgetContainerConfig->setBackground("#ffffff");
            $referencesWidgetContainerConfig->setPosition(0);
            $referencesWidgetContainerConfig->setLayout(array(1));
            $referencesWidgetContainerConfig->setWidgetContainer($referencesWidgetContainer);
            $this->om->persist($referencesWidget);
            $this->om->persist($referencesWidgetInstance);
            $this->om->persist($referencesWidgetContainer);

            $learningUnitDocument->addWidgetContainer($referencesWidgetContainer);
        } else {
            $referencesDocument = $this->resourceManager->getResourceFromNode($referencesNode);
        }

        $externalReferencesNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "External references",
                'parent'=>$referencesNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->textType->getId()
            ]
        );
        if (empty($externalReferencesNode)) {
            //  This references document should hold
            //  - a widget rendering a simple page of links to external resources
            $externalReferencesNode = new ResourceNode();
            $externalReferencesNode->setName("External references");
            $externalReferencesNode->setWorkspace($workspace);
            $externalReferencesNode->setResourceType($this->textType);
            $externalReferencesNode->setParent($referencesNode);
            $externalReferencesNode->setCreator($user);
            $externalReferencesNode->setMimeType("custom/text");
            $externalReferencesText = new Text();
            $externalReferencesText->setResourceNode($externalReferencesNode);
            $externalReferencesText->setName("External references");
            $this->om->persist($externalReferencesNode);
            $this->om->persist($externalReferencesText);
            
            // Preparing widgets for the document
            $externalReferencesWidget = new ResourceWidget();
            $externalReferencesWidget->setResourceNode($externalReferencesNode);
            $externalReferencesWidget->setShowResourceHeader(true);
            $externalReferencesWidgetInstance = new WidgetInstance();
            $externalReferencesWidgetInstance->setWidget($this->widgetType);
            $externalReferencesWidgetInstance->setDataSource($this->resourceDataSource);
            $externalReferencesWidget->setWidgetInstance($externalReferencesWidgetInstance);
            $externalReferencesWidgetInstanceConfig = new WidgetInstanceConfig();
            $externalReferencesWidgetInstanceConfig->setType("resource");
            $externalReferencesWidgetInstanceConfig->setWidgetInstance($externalReferencesWidgetInstance);
            $externalReferencesWidgetContainer = new WidgetContainer();
            $externalReferencesWidgetContainer->addInstance($externalReferencesWidgetInstance);
            $externalReferencesWidgetInstance->setContainer($externalReferencesWidgetContainer);
            $externalReferencesWidgetContainerConfig = new WidgetContainerConfig();
            $externalReferencesWidgetContainerConfig->setBackgroundType("color");
            $externalReferencesWidgetContainerConfig->setBackground("#ffffff");
            $externalReferencesWidgetContainerConfig->setPosition(0);
            $externalReferencesWidgetContainerConfig->setLayout(array(1));
            $externalReferencesWidgetContainerConfig->setWidgetContainer($externalReferencesWidgetContainer);
            $this->om->persist($externalReferencesWidget);
            $this->om->persist($externalReferencesWidgetInstance);
            $this->om->persist($externalReferencesWidgetContainer);

            $referencesDocument->addWidgetContainer($externalReferencesWidgetContainer);
            $this->om->persist($referencesDocument);
        }
        $this->nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $externalReferencesNode);
        // */

        $successData['generate_ipip_content'][] = [
            'data' => $data,
            'log' => "learning unit {$learningUnit} created",
        ];
    }



    /**
     *
     */
    public function getClass()
    {
        return Workspace::class;
    }

    /**
     *
     */
    public function getAction()
    {
        return ['workspace', 'generate_ipip_content'];
    }

    /**
     *
     */
    public function getSchema(array $options = [], array $extra = [])
    {
        $learning_unit = [
            '$schema' => 'http:\/\/json-schema.org\/draft-04\/schema#',
            'type' => 'object',
            'properties' => [
                'curriculum' => [
                    'type' => 'string',
                    'description' => 'Curriculum (workspace) name'
                ],
                'course' => [
                  'type' => 'string',
                  'description' => 'Course (binder) name'
                ],
                'module' => [
                  'type' => 'string',
                  'description' => 'Module (binder) name'
                ],
                'learning_unit' => [
                  'type' => 'string',
                  'description' => 'Learning unit (document) name'
                ]
            ],
            'claroline' => [
                'class' => Workspace::class,
            ],
        ];
        return [
            '$root' => json_decode(json_encode($learning_unit)),
        ];
    }

    /**
     *
     */
    public function getOptions()
    {
        //in an ideal world this should be removed but for now it's an easy fix
        return [Options::FORCE_FLUSH];
    }
}
