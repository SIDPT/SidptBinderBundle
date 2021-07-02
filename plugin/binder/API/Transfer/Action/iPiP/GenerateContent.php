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
use Claroline\CoreBundle\Entity\Widget\Type\ListWidget;
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
 * Generate or update ipip content
 *
 * 06/2021 :
 * - Replace binders by document with a single data list of children resources
 * - Replace the learning unit description resource by a simple widget
 * with a specified template
 * (or maybe use the resource description for it?
 *  just in case, also set the node description with the same template for now)
 *  
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
    private $resourceWidgetsRepo;
    private $listWidgetsRepo;

    private $binderType;
    private $documentType;
    private $directoryType;
    private $lessonType;
    private $exerciseType;
    private $textType;
    private $resourceDataSource;
    private $resourcesListDataSource;
    private $resourceWidgetType;
    private $listWidgetType;


    private $nodeSeralizer;

    // Tags hierarchy
    private $contentLevel;
    private $professionnalProfile;
    private $estimatedTime;
    private $includedResources;

    

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
        $this->resourceWidgetsRepo = $this->om->getRepository(ResourceWidget::class);
        $this->listWidgetsRepo = $this->om->getRepository(ListWidget::class);
        
        
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
        $this->resourcesListDataSource = $dataSourceRepo->findOneBy(
            ['name' => 'resources']
        );

        $this->resourceWidgetType = $widgetsTypeRepo->findOneBy(
            [ 'name' => 'resource' ]
        );
        $this->listWidgetType = $widgetsTypeRepo->findOneBy(
            [ 'name' => 'list' ]
        );
        $this->nodeSeralizer = $this->serializer->get(ResourceNode::class);
    }


    /**
     *
     */
    public function execute(array $data, &$successData = [])
    {
        
        $user = $this->tokenStorage->getToken()->getUser();

        $curriculum = isset($data['curriculum']) ? trim($data['curriculum']) : null;
        $course = isset($data['course']) ? trim($data['course']) : null;
        $module = isset($data['module']) ? trim($data['module']) : null;
        $learningUnit = isset($data['learning_unit']) ? trim($data['learning_unit']) : null;
        $learningOutcome = isset($data['learning_outcome']) ? trim($data['learning_outcome']) :null;
        

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
        // retrieve tag for next usage
        $curriculumTag = $this->tagManager->getOnePlatformTagByName($curriculum);
        
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
                'workspace' => $workspace->getId()
            ]
        );
        if (empty($courseNode)) {
            $courseNode = new ResourceNode();
            $courseNode->setName($course);
            $courseNode->setWorkspace($workspace);
            $courseNode->setParent($curriculumNode);
            $courseNode->setCreator($user);

            $courseNode->setResourceType($this->documentType);
            $courseNode->setMimeType("custom/sidpt_document");

            $courseDocument = new Document();
            $courseDocument->setResourceNode($courseNode);

        } else {
            // check if resource is binder
            if ($courseNode->getResourceType()->getId() == $this->binderType->getId()) {
                // Replace by document
                $courseBinder = $this->resourceManager->getResourceFromNode($courseNode);
                
                $courseNode->setResourceType($this->documentType);
                $courseNode->setMimeType("custom/sidpt_document");

                $courseDocument = new Document();
                $courseDocument->setResourceNode($courseNode);
                
                $this->om->remove($courseBinder);
            } else {
                $courseDocument = $this->resourceManager->getResourceFromNode($courseNode);
            }
        }

        $courseDocument->setName($course);
        $this->addOrUpdateResourceListWidget($courseDocument, $courseNode, "Modules");
        $this->om->persist($courseDocument);
        $this->om->persist($courseNode);
        $this->om->flush();

        $this->tagManager->tagData(
            ['Course', $course],
            [ 0 => [
                'id'=> $courseNode->getUuid(),
                'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
                'name' => "{$curriculum}|{$course}"
            ]]
        );

        // Add the course tag under the curriculum tag
        $courseTag = $this->tagManager->getOnePlatformTagByName($course);
        $curriculumTag->addLinkedTag($courseTag);
        $this->om->persist($curriculumTag);
        $this->om->persist($courseTag);
        $this->om->flush();

        $this->nodeSeralizer->deserializeRights($curriculumNodeData['rights'], $courseNode);


        $courseSummaryNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => "Summary",
                'parent'=> $courseNode->getId(),
                'workspace' => $workspace->getId(),
                'resourceType' => $this->documentType->getId()
            ]
        );
        // Delete previous summary nodes
        if (!empty($courseSummaryNode)) {
            $courseSummary = $this->resourceManager->getResourceFromNode($courseSummaryNode);

            $this->om->remove($courseSummary);
            $this->om->remove($courseSummaryNode);
            $this->om->flush();
        }

        // No module provided, ending the treatment
        if (empty($module)) {
            return;
        }
        

        $moduleNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => $module,
                'parent'=>$courseNode->getId(),
                'workspace' => $workspace->getId()
            ]
        );
        if (empty($moduleNode)) {
            $moduleNode = new ResourceNode();
            $moduleNode->setName($module);
            $moduleNode->setWorkspace($workspace);
            $moduleNode->setParent($courseNode);
            $moduleNode->setCreator($user);

            $moduleNode->setResourceType($this->documentType);
            $moduleNode->setMimeType("custom/sidpt_document");

            $moduleDocument = new Document();
            $moduleDocument->setResourceNode($moduleNode);

        } else {
            // check if existing resource is binder
            if ($moduleNode->getResourceType()->getId() == $this->binderType->getId()) {
                // Replace by document
                
                
                $moduleNode->setResourceType($this->documentType);
                $moduleNode->setMimeType("custom/sidpt_document");

                $moduleDocument = new Document();
                $moduleDocument->setResourceNode($moduleNode);
                
                
                $this->om->remove($moduleBinder);
            } else {
                $moduleDocument = $this->resourceManager->getResourceFromNode($moduleNode);
            }
        }

        $moduleDocument->setName($module);
        $this->addOrUpdateResourceListWidget($moduleDocument, $moduleNode, "Learning units");
        $this->om->persist($moduleNode);
        $this->om->persist($moduleDocument);
        $this->om->flush();

        $this->tagManager->tagData(
            ['Module', $module],
            [ 0 => [
                'id'=> $moduleNode->getUuid(),
                'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
                'name' => "{$curriculum}|{$course}|{$module}"
            ]]
        );
        // Add the module tag under the course tag
        $moduleTag = $this->tagManager->getOnePlatformTagByName($module);
        $courseTag->addLinkedTag($moduleTag);
        $this->om->persist($courseTag);
        $this->om->persist($moduleTag);
        $this->om->flush();

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
        // Delete previous summary nodes
        if (!empty($moduleSummaryNode)) {
            $moduleSummary = $this->resourceManager->getResourceFromNode($moduleSummaryNode);
            $this->om->remove($moduleSummary);
            $this->om->remove($moduleSummaryNode);
            $this->om->flush();
        }

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
        $learningOutcomeContent = $learningOutcome ??
                <<<HTML
                    <p><span style="color: #ff0000;"><strong>Author please fill this section</strong></span></p>
                HTML;

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

        } else {
            $learningUnitDocument = $this->resourceManager
                ->getResourceFromNode($learningUnitNode);
        }

        $learningUnitDocument->setName($learningUnit);
        $learningUnitDocument->setShowOverview(true);
        $learningUnitDocument->setWidgetsPagination(true);
        $learningUnitNode->setDescription(
<<<HTML
<table class="table table-striped table-hover table-condensed data-table" style="height: 133px; width: 100%; border-collapse: collapse; margin-left: auto; margin-right: auto;" border="1" cellspacing="5px" cellpadding="20px">
<tbody>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">Learning Unit</td>
<td class="text-left string-cell" style="width: 50%; height: 19px;"><a id="{{ resource.resourceNode.slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.slug}}">{{ resource.resourceNode.name }}</a></td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">Module</td>
<td class="text-left string-cell" style="width: 50%; height: 19px;"><a id="{{ resource.resourceNode.path[-2].slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-2].slug}}">{{ resource.resourceNode.path[-2].name }}</a></td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">Course</td>
<td class="text-left string-cell" style="width: 50%; height: 19px;"><a id="{{ resource.resourceNode.path[-3].slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-3].slug}}">{{ resource.resourceNode.path[-3].name }}</a></td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">Who is it for?</td>
<td style="width: 50%; height: 19px;">{{#resource.resourceNode.tags["Professional profile"]}}{{keys}}{{/resource.resourceNode.tags["Professional profile"]}}</td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">What is included?</td>
<td style="width: 50%; height: 19px;">{{#resource.resourceNode.tags["Included resource type"]}}{{keys}}{{/resource.resourceNode.tags["Included resource type"]}}</td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">How long will it take?</td>
<td style="width: 50%; height: 19px;">{{#resource.resourceNode.tags["Time frame"]}}{{keys}}{{/resource.resourceNode.tags["Time frame"]}}</td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">Last updated</td>
<td style="width: 50%; height: 19px;">{{#resource.resourceNode.meta.updated}}{{formatDate}}{{/resource.resourceNode.meta.updated}}</td>
</tr>
</tbody>
</table>
<h3>Learning outcome</h3>
<p>{$learningOutcomeContent}</p>
<p>{{#resource.resourceNode.tags["Disclaimer"] }}</p>
<h3>Disclaimer</h3>
<p class="p1">This learning unit contains images that may not be accessible to some learners. This content is used to support learning. Whenever possible the information presented in the images is explained in the text.</p>
<p>{{/resource.resourceNode.tags["Disclaimer"] }}</p>
HTML);

        
        $this->om->persist($learningUnitNode);
        $this->om->persist($learningUnitDocument);
        $this->om->flush();

        $this->tagManager->tagData(
            ['Learning unit', $learningUnit],
            [ 0 => [
                'id'=> $learningUnitNode->getUuid(),
                'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
                'name' => "{$curriculum}|{$course}|{$module}|{$learningUnit}"
            ]]
        );
        // Add the learningUnit tag under the module tag
        $learningUnitTag = $this->tagManager->getOnePlatformTagByName($learningUnit);
        $moduleTag->addLinkedTag($learningUnitTag);
        $this->om->persist($moduleTag);
        $this->om->persist($learningUnitTag);
        $this->om->flush();

        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $learningUnitNode
        );

        

        // for each learning unit, pre-creating the default resources and widget
        // that is
        
        // - a practice exercise:
        $practiceNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Practice",
            $this->exerciseType
        );
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $practiceNode
        );
        // - A theroy lesson
        $theoryNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Theory",
            $this->lessonType
        );
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $theoryNode
        );

        // - An assessment exercice
        $assessmentNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Assessment",
            $this->exerciseType
        );
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $assessmentNode
        );

        
        // - An activity text
        $activityNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Activity",
            $this->textType
        );
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $activityNode
        );
        
        // - A references document
        $referencesNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "References",
            $this->documentType
        );
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $referencesNode
        );
        $referencesDocument = $this->resourceManager->getResourceFromNode($referencesNode);
        
        // The reference document contains 2 sections :
        // - an external reference textual section
        $externalReferencesNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $referencesNode,
            "External references",
            $this->textType
        );
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $externalReferencesNode
        );
        // - an internal reference folder, to store a hierarchy of shortcuts
        $internalReferencesNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $referencesNode,
            "IPIP references",
            $this->directoryType
        );
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $internalReferencesNode
        );
        // */

        $successData['generate_ipip_content'][] = [
            'data' => $data,
            'log' => "learning unit {$curriculum}|{$course}|{$module}|{$learningUnit} created or updated",
        ];
    }

    /**
     * [addResourceWidget description]
     * @param [type] $document     [description]
     * @param [type] $resourceNode [description]
     */
    public function addResourceWidget($document, $resourceNode, $name = null)
    {
        $newWidget = new ResourceWidget();
        $newWidget->setResourceNode($resourceNode);
        $newWidget->setShowResourceHeader(false);

        $newWidgetInstance = new WidgetInstance();
        $newWidgetInstance->setWidget($this->resourceWidgetType);
        $newWidgetInstance->setDataSource($this->resourceDataSource);
        $newWidget->setWidgetInstance($newWidgetInstance);

        $newWidgetInstanceConfig = new WidgetInstanceConfig();
        $newWidgetInstanceConfig->setType("resource");
        $newWidgetInstanceConfig->setWidgetInstance($newWidgetInstance);

        $newWidgetContainer = new WidgetContainer();
        $newWidgetContainer->addInstance($newWidgetInstance);
        $newWidgetInstance->setContainer($newWidgetContainer);

        $newWidgetContainerConfig = new WidgetContainerConfig();
        $newWidgetContainerConfig->setName($name);
        $newWidgetContainerConfig->setBackgroundType("color");
        $newWidgetContainerConfig->setBackground("#ffffff");
        $newWidgetContainerConfig->setPosition(0);
        $newWidgetContainerConfig->setLayout(array(1));
        $newWidgetContainerConfig->setWidgetContainer($newWidgetContainer);

        $this->om->persist($newWidget);
        $this->om->persist($newWidgetInstance);
        $this->om->persist($newWidgetContainer);

        $document->addWidgetContainer($newWidgetContainer);
        $this->om->persist($document);
    }


    public function addOrUpdateDocumentSubObject(
        $user,
        $documentNode,
        $nodeName,
        $resourceType
    ) {
        $document = $this->resourceManager->getResourceFromNode($documentNode);
        $subNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => $nodeName,
                'parent'=>$documentNode->getId(),
                'resourceType' => $resourceType->getId()
            ]
        );
        if (empty($subNode)) {
            $subNode = new ResourceNode();
            $subNode->setName($nodeName);
            $subNode->setWorkspace($documentNode->getWorkspace());
            $subNode->setResourceType($resourceType);
            $subNode->setParent($documentNode);
            $subNode->setCreator($user);
            $subNode->setMimeType("custom/".$resourceType->getName());

            $resourceclass = $resourceType->getClass();
            $subResource = new $resourceclass();
            $subResource->setResourceNode($subNode);
            $subResource->setName($nodeName);

            if ($resourceType->getName() == "ujm_exercise") {
                if ($nodeName == "Practice") {
                    $subResource->setType(ExerciseType::CONCEPTUALIZATION);
                } else if ($nodeName == "Assessment") {
                    $subResource->setType(ExerciseType::SUMMATIVE);
                }
            }
            
            $this->om->persist($subNode);
            $this->om->persist($subResource);
            $this->om->persist($document);

            $this->addResourceWidget($document, $subNode, $nodeName);

        } else { // Update the document or node
            $subNodeWidgets = $this->resourceWidgetsRepo->findBy(
                [
                    'resourceNode' => $subNode->getId()
                ]
            );
            if (!empty($subNodeWidgets)) {
                foreach ($subNodeWidgets as $widget) {
                    $widget->setShowResourceHeader(false);
                    $instance = $widget->getWidgetInstance();
                    $container = $instance->getContainer();
                    $containerConfig = $container->getWidgetContainerConfigs()->first();
                    $containerConfig->setName($nodeName);

                    $this->om->persist($widget);
                }
            }
        }
        $this->om->flush();
        return $subNode;
    }

    /**
     * [addResourceWidget description]
     * @param [type] $document     [description]
     * @param [type] $resourceNode [description]
     */
    public function addOrUpdateResourceListWidget($document, $parentNode, $name = null)
    {
        if ($document->getWidgetContainers()->isEmpty()) {
            $newWidget = new ListWidget();
            $newWidget->setFilters(
                [   0 => [
                        "property" => "parent",
                        "value" => [
                            "id" => $parentNode->getUuid(),
                            "name" => $parentNode->getName()
                        ],
                        "locked" => true
                    ]
                ]
            );
            $newWidget->setDisplay("table-min");
            $newWidget->setActions(false);
            $newWidget->setCount(true);
            $newWidget->setDisplayedColumns(["name"]);
            

            $newWidgetInstance = new WidgetInstance();
            $newWidgetInstance->setWidget($this->listWidgetType);
            $newWidgetInstance->setDataSource($this->resourcesListDataSource);
            $newWidget->setWidgetInstance($newWidgetInstance);
            $newWidgetInstanceConfig = new WidgetInstanceConfig();
            $newWidgetInstanceConfig->setType("list");
            $newWidgetInstanceConfig->setWidgetInstance($newWidgetInstance);
            $newWidgetContainer = new WidgetContainer();
            $newWidgetContainer->addInstance($newWidgetInstance);
            $newWidgetInstance->setContainer($newWidgetContainer);
            $newWidgetContainerConfig = new WidgetContainerConfig();
            $newWidgetContainerConfig->setName($name);
            $newWidgetContainerConfig->setBackgroundType("color");
            $newWidgetContainerConfig->setBackground("#ffffff");
            $newWidgetContainerConfig->setPosition(0);
            $newWidgetContainerConfig->setLayout(array(1));
            $newWidgetContainerConfig->setWidgetContainer($newWidgetContainer);
            $this->om->persist($newWidget);
            $this->om->persist($newWidgetInstance);
            $this->om->persist($newWidgetContainer);

            $document->addWidgetContainer($newWidgetContainer);

        } else {
            $container = $document->getWidgetContainers()->first();
            $containerConfig = $container->getWidgetContainerConfigs()->first();
            $instance = $container->getInstances()->first();

            $widget = $this->listWidgetsRepo->findOneBy(
                [
                    "widgetInstance" => $instance->getId()
                ]
            );

            $widget->setFilters(
                [   0 => [
                        "property" => "parent",
                        "value" => [
                            "id" => $parentNode->getUuid(),
                            "name" => $parentNode->getName()
                        ],
                        "locked" => true
                    ]
                ]
            );
            $widget->setDisplay("table-min");
            $widget->setActions(false);
            $widget->setCount(true);
            $widget->setDisplayedColumns(["name"]);
            $containerConfig->setName($name);

            $this->om->persist($widget);
            $this->om->persist($containerConfig);
        }
        $this->om->persist($document);
        $this->om->flush();
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
                ],
                'learning_outcome' => [
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
