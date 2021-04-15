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

class GenerateContent extends AbstractAction
{
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
    }


    public function execute(array $data, &$successData = [])
    {
        $workspaceRepo = $this->om->getRepository(Workspace::class);
        $tagsRepo = $this->om->getRepository(Tag::class);
        $taggedObjectsRepo = $this->om->getRepository(TaggedObject::class);
        $resourceNodeRepo = $this->om->getRepository(ResourceNode::class);
        $binderRepo = $this->om->getRepository(Binder::class);
        $documentRepo = $this->om->getRepository(Document::class);
        $typesRepo = $this->om->getRepository(ResourceType::class);
        $homeTabsRepo = $this->om->getRepository(HomeTab::class);
        $dataSourceRepo = $this->om->getRepository(DataSource::class);
        $widgetsTypeRepo = $this->om->getRepository(Widget::class);


        $binderType = $typesRepo->findOneBy([
            'name' => 'sidpt_binder'
        ]);
        $documentType = $typesRepo->findOneBy([
            'name' => 'sidpt_document'
        ]);

        $directoryType = $typesRepo->findOneBy([
            'name' => 'directory'
        ]);

        $lessonType = $typesRepo->findOneBy([
            'name' => 'icap_lesson'
        ]);

        $exerciseType = $typesRepo->findOneBy([
            'name' => 'ujm_exercise'
        ]);

        $textType = $typesRepo->findOneBy([
            'name' => 'text'
        ]);

        $types = array_map(function (ResourceType $type) {
            return [ "name" => $type->getName() ];
        }, $this->om->getRepository(ResourceType::class)->findAll());

        $documentSeralizer = $this->serializer->get(Document::class);
        $nodeSeralizer = $this->serializer->get(ResourceNode::class);

        $user = $this->tokenStorage->getToken()->getUser();
        
        $resourceDataSource = $dataSourceRepo->findOneBy(['name' => 'resource']);
        $widgetType = $widgetsTypeRepo->findOneBy(['name' => 'resource']);


        $curriculum = trim($data['curriculum']);
        $course = trim($data['course']);
        $module = trim($data['module']);
        $learningUnit = trim($data['learning_unit']);
            
        ///// WORKSPACE / CURRICULUM
        // Check if curriculum exist
        $workspace = $workspaceRepo->findOneBy(['name' => $curriculum]);
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
        
            // From workspace manager (seems mandatory to do it this way for default rights ?)
            $workspace = $this->crud->create(Workspace::class, $data);
            $model = $workspace->getWorkspaceModel();
            $workspace = $this->workspaceManager->copy($model, $workspace, false);
            $workspace = $this->serializer->get(Workspace::class)->deserialize($data, $workspace);

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
        $curriculumNode = $resourceNodeRepo->findOneBy([
            'parent' => null,
            'workspace' => $workspace->getId()
        ]);
        $curriculumNodeData = $nodeSeralizer->serialize($curriculumNode);
        
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
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $curriculumNode);


        $curriculumSummaryNode = $resourceNodeRepo->findOneBy([
            'name' => "Summary",
            'parent' => $curriculumNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $documentType->getId(),
        ]);
        if (empty($curriculumSummaryNode)) {
            $curriculumSummaryNode = new ResourceNode();
            $curriculumSummaryNode->setName("Summary");
            $curriculumSummaryNode->setWorkspace($workspace);
            $curriculumSummaryNode->setResourceType($documentType);
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
        }
        
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $curriculumSummaryNode);



        $courseNode = $resourceNodeRepo->findOneBy([
            'name' => $course,
            'parent'=> $curriculumNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $binderType->getId()
        ]);
        if (empty($courseNode)) {
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
            
            $this->tagManager->tagData(
                ['Course',$curriculum, $course],
                [ 0 => [
                        'id'=> $courseNode->getUuid(),
                        'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode"
                ]]
            );


            $tabs = $homeTabsRepo->findBy([
                'workspace' => $workspace->getUuid(),
                'parent' => null
            ]);

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
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $courseNode);


        

        $courseSummaryNode = $resourceNodeRepo->findOneBy([
            'name' => "Summary",
            'parent'=> $courseNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $documentType->getId()
        ]);
        if (empty($courseSummaryNode)) {
            $courseSummaryNode = new ResourceNode();
            $courseSummaryNode->setName("Summary");
            $courseSummaryNode->setWorkspace($workspace);
            $courseSummaryNode->setResourceType($documentType);
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
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $courseSummaryNode);

        // Check if module exist in the course
        // module is also a binder
        
        $moduleNode = $resourceNodeRepo->findOneBy([
            'name' => $module,
            'parent'=>$courseNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $binderType->getId()
        ]);
        if (empty($moduleNode)) {
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
            
            $this->tagManager->tagData(
                ['Module', $curriculum, $course, $module],
                [ 0 => [
                        'id'=> $moduleNode->getUuid(),
                        'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode"
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
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $moduleNode);

        $moduleSummaryNode = $resourceNodeRepo->findOneBy([
            'name' => "Summary",
            'parent'=> $moduleNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $documentType->getId()
        ]);
        if (empty($moduleSummaryNode)) {
            $moduleSummaryNode = new ResourceNode();
            $moduleSummaryNode->setName("Summary");
            $moduleSummaryNode->setWorkspace($workspace);
            $moduleSummaryNode->setResourceType($documentType);
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
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $moduleSummaryNode);

        // Check if learning unit exist within the module
        // a learning unit is a document
        $learningUnitNode = $resourceNodeRepo->findOneBy([
            'name' => $learningUnit,
            'parent'=>$moduleNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $documentType->getId()
        ]);
        if (empty($learningUnitNode)) {
            $learningUnitNode = new ResourceNode();
            $learningUnitNode->setName($learningUnit);
            $learningUnitNode->setWorkspace($workspace);
            $learningUnitNode->setResourceType($documentType);
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
                        'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode"
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
            $learningUnitDocument = $this->resourceManager->getResourceFromNode($learningUnitNode);
        }
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $learningUnitNode);

        // for each learning unit, pre-creating the default resources and widget
        // that is :
        // - a practice "exercise":
        $practiceNode = $resourceNodeRepo->findOneBy([
            'name' => "Practice",
            'parent'=>$learningUnitNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $exerciseType->getId()
        ]);
        if (empty($practiceNode)) {
            $practiceNode = new ResourceNode();
            $practiceNode->setName("Practice");
            $practiceNode->setWorkspace($workspace);
            $practiceNode->setResourceType($exerciseType);
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
            $practiceWidgetInstance = new WidgetInstance();
            $practiceWidgetInstance->setWidget($widgetType);
            $practiceWidgetInstance->setDataSource($resourceDataSource);
            $practiceWidget->setWidgetInstance($practiceWidgetInstance);
            $practiceWidgetInstanceConfig = new WidgetInstanceConfig();
            $practiceWidgetInstanceConfig->setType("resource");
            $practiceWidgetInstanceConfig->setWidgetInstance($practiceWidgetInstance);
            $practiceWidgetContainer = new WidgetContainer();
            $practiceWidgetContainer->addInstance($practiceWidgetInstance);
            $practiceWidgetInstance->setContainer($practiceWidgetContainer);
            $practiceWidgetContainerConfig = new WidgetContainerConfig();
            $practiceWidgetContainerConfig->setName("Practice");
            $practiceWidgetContainerConfig->setBackgroundType("color");
            $practiceWidgetContainerConfig->setBackground("#ffffff");
            $practiceWidgetContainerConfig->setPosition(0);
            $practiceWidgetContainerConfig->setLayout(array(1));
            $practiceWidgetContainerConfig->setWidgetContainer($practiceWidgetContainer);
            $this->om->persist($practiceWidget);
            $this->om->persist($practiceWidgetInstance);
            $this->om->persist($practiceWidgetContainer);

            $learningUnitDocument->addWidgetContainer($practiceWidgetContainer);

        }
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $practiceNode);

        $theoryNode = $resourceNodeRepo->findOneBy([
            'name' => "Theory",
            'parent'=>$learningUnitNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $lessonType->getId()
        ]);
        if (empty($theoryNode)) {
            // - a theory "lesson":
            $theoryNode = new ResourceNode();
            $theoryNode->setName("Theory");
            $theoryNode->setWorkspace($workspace);
            $theoryNode->setResourceType($lessonType);
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
            $theoryWidgetInstance = new WidgetInstance();
            $theoryWidgetInstance->setWidget($widgetType);
            $theoryWidgetInstance->setDataSource($resourceDataSource);
            $theoryWidget->setWidgetInstance($theoryWidgetInstance);
            $theoryWidgetInstanceConfig = new WidgetInstanceConfig();
            $theoryWidgetInstanceConfig->setType("resource");
            $theoryWidgetInstanceConfig->setWidgetInstance($theoryWidgetInstance);
            $theoryWidgetContainer = new WidgetContainer();
            $theoryWidgetContainer->addInstance($theoryWidgetInstance);
            $theoryWidgetInstance->setContainer($theoryWidgetContainer);
            $theoryWidgetContainerConfig = new WidgetContainerConfig();
            $theoryWidgetContainerConfig->setName("Theory");
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
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $theoryNode);

        $assessmentNode = $resourceNodeRepo->findOneBy([
            'name' => "Assessment",
            'parent'=>$learningUnitNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $exerciseType->getId()
        ]);
        if (empty($assessmentNode)) {
             // - an assessment "Exercise":
            $assessmentNode = new ResourceNode();
            $assessmentNode->setName("Assessment");
            $assessmentNode->setWorkspace($workspace);
            $assessmentNode->setResourceType($exerciseType);
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
            $assessmentWidgetInstance = new WidgetInstance();
            $assessmentWidgetInstance->setWidget($widgetType);
            $assessmentWidgetInstance->setDataSource($resourceDataSource);
            $assessmentWidget->setWidgetInstance($assessmentWidgetInstance);
            $assessmentWidgetInstanceConfig = new WidgetInstanceConfig();
            $assessmentWidgetInstanceConfig->setType("resource");
            $assessmentWidgetInstanceConfig->setWidgetInstance($assessmentWidgetInstance);
            $assessmentWidgetContainer = new WidgetContainer();
            $assessmentWidgetContainer->addInstance($assessmentWidgetInstance);
            $assessmentWidgetInstance->setContainer($assessmentWidgetContainer);
            $assessmentWidgetContainerConfig = new WidgetContainerConfig();
            $assessmentWidgetContainerConfig->setName("Assessment");
            $assessmentWidgetContainerConfig->setBackgroundType("color");
            $assessmentWidgetContainerConfig->setBackground("#ffffff");
            $assessmentWidgetContainerConfig->setPosition(0);
            $assessmentWidgetContainerConfig->setLayout(array(1));
            $assessmentWidgetContainerConfig->setWidgetContainer($assessmentWidgetContainer);
            $this->om->persist($assessmentWidget);
            $this->om->persist($assessmentWidgetInstance);
            $this->om->persist($assessmentWidgetContainer);

            $learningUnitDocument->addWidgetContainer($assessmentWidgetContainer);
        }
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $assessmentNode);


        $activityNode = $resourceNodeRepo->findOneBy([
            'name' => "Activity",
            'parent'=>$learningUnitNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $textType->getId()
        ]);
        if (empty($activityNode)) {
            // - an activity "text":
            $activityNode = new ResourceNode();
            $activityNode->setName("Activity");
            $activityNode->setWorkspace($workspace);
            $activityNode->setResourceType($textType);
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
            $activityWidgetInstance = new WidgetInstance();
            $activityWidgetInstance->setWidget($widgetType);
            $activityWidgetInstance->setDataSource($resourceDataSource);
            $activityWidget->setWidgetInstance($activityWidgetInstance);
            $activityWidgetInstanceConfig = new WidgetInstanceConfig();
            $activityWidgetInstanceConfig->setType("resource");
            $activityWidgetInstanceConfig->setWidgetInstance($activityWidgetInstance);
            $activityWidgetContainer = new WidgetContainer();
            $activityWidgetContainer->addInstance($activityWidgetInstance);
            $activityWidgetInstance->setContainer($activityWidgetContainer);
            $activityWidgetContainerConfig = new WidgetContainerConfig();
            $activityWidgetContainerConfig->setName("activity");
            $activityWidgetContainerConfig->setBackgroundType("color");
            $activityWidgetContainerConfig->setBackground("#ffffff");
            $activityWidgetContainerConfig->setPosition(0);
            $activityWidgetContainerConfig->setLayout(array(1));
            $activityWidgetContainerConfig->setWidgetContainer($activityWidgetContainer);
            $this->om->persist($activityWidget);
            $this->om->persist($activityWidgetInstance);
            $this->om->persist($activityWidgetContainer);

            $learningUnitDocument->addWidgetContainer($activityWidgetContainer);
        }
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $activityNode);

        $referencesNode = $resourceNodeRepo->findOneBy([
            'name' => "References",
            'parent'=>$learningUnitNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $documentType->getId()
        ]);
        if (empty($referencesNode)) {
            // - A references "document"
            $referencesNode = new ResourceNode();
            $referencesNode->setName("References");
            $referencesNode->setWorkspace($workspace);
            $referencesNode->setResourceType($documentType);
            $referencesNode->setParent($learningUnitNode);
            $referencesNode->setCreator($user);
            $referencesNode->setMimeType("custom/sidpt_document");
            $referencesDocument = new Document();
            $referencesDocument->setResourceNode($referencesNode);
            $referencesDocument->setName("References");
            $this->om->persist($referencesNode);
            $this->om->persist($referencesDocument);

            $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $referencesNode);

            $referencesWidget = new ResourceWidget();
            $referencesWidget->setResourceNode($referencesNode);
            $referencesWidgetInstance = new WidgetInstance();
            $referencesWidgetInstance->setWidget($widgetType);
            $referencesWidgetInstance->setDataSource($resourceDataSource);
            $referencesWidget->setWidgetInstance($referencesWidgetInstance);
            $referencesWidgetInstanceConfig = new WidgetInstanceConfig();
            $referencesWidgetInstanceConfig->setType("resource");
            $referencesWidgetInstanceConfig->setWidgetInstance($referencesWidgetInstance);
            $referencesWidgetContainer = new WidgetContainer();
            $referencesWidgetContainer->addInstance($referencesWidgetInstance);
            $referencesWidgetInstance->setContainer($referencesWidgetContainer);
            $referencesWidgetContainerConfig = new WidgetContainerConfig();
            $referencesWidgetContainerConfig->setName("references");
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

        $externalReferencesNode = $resourceNodeRepo->findOneBy([
            'name' => "External references",
            'parent'=>$referencesNode->getId(),
            'workspace' => $workspace->getId(),
            'resourceType' => $textType->getId()
        ]);
        if (empty($externalReferencesNode)) {
            //  This references document should hold
            //  - a widget rendering a simple page of links to external resources
            $externalReferencesNode = new ResourceNode();
            $externalReferencesNode->setName("External references");
            $externalReferencesNode->setWorkspace($workspace);
            $externalReferencesNode->setResourceType($textType);
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
            $externalReferencesWidgetInstance = new WidgetInstance();
            $externalReferencesWidgetInstance->setWidget($widgetType);
            $externalReferencesWidgetInstance->setDataSource($resourceDataSource);
            $externalReferencesWidget->setWidgetInstance($externalReferencesWidgetInstance);
            $externalReferencesWidgetInstanceConfig = new WidgetInstanceConfig();
            $externalReferencesWidgetInstanceConfig->setType("resource");
            $externalReferencesWidgetInstanceConfig->setWidgetInstance($externalReferencesWidgetInstance);
            $externalReferencesWidgetContainer = new WidgetContainer();
            $externalReferencesWidgetContainer->addInstance($externalReferencesWidgetInstance);
            $externalReferencesWidgetInstance->setContainer($externalReferencesWidgetContainer);
            $externalReferencesWidgetContainerConfig = new WidgetContainerConfig();
            $externalReferencesWidgetContainerConfig->setName("External references");
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
        $nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $externalReferencesNode);
        // */

        $successData['generate_ipip_content'][] = [
            'data' => $data,
            'log' => "learning unit {$data['learning_unit']} created with rights {$rightsToDisplay}",
        ];

    }

    public function getClass()
    {
        return Workspace::class;
    }

    public function getAction()
    {
        return ['workspace', 'generate_ipip_content'];
    }

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

    public function getOptions()
    {
        //in an ideal world this should be removed but for now it's an easy fix
        return [Options::FORCE_FLUSH];
    }

}