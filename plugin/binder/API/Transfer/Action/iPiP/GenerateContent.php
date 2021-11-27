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
        } else {
            // Update workspace :
            // Set to open the "summary" document instead of the tab view ?
        }


        // make the curriculum tag
        $curriculumTag = $this->tagManager->getOnePlatformTagByName($curriculum);
        if (empty($curriculumTag)) {
            $curriculumTag = new Tag();
            $curriculumTag->setName($curriculum);
            $this->om->persist($curriculumTag);
        }
        // Tag the workspace (if not already done)
        $this->tagManager->tagObject(
            ['Content level/Curriculum',$curriculumTag->getPath()],
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
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $curriculumNode
        );

        /* TODO : make manual updates of summaries

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
        } else {
            // Updating the summary document
        }
        $this->nodeSeralizer->deserializeRights(
            $curriculumNodeData['rights'],
            $curriculumSummaryNode
        );
        */

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
        $courseDocument = null;
        if (empty($courseNode)) {
            $courseNode = new ResourceNode();
            $courseNode->setName($course);
            $courseNode->setWorkspace($workspace);
            $courseNode->setParent($curriculumNode);
            $courseNode->setCreator($user);

            $courseNode->setResourceType($this->documentType);
            $courseNode->setMimeType("custom/sidpt_document");
            $this->om->persist($courseNode);
        } else {
            // check if resource is binder
            if ($courseNode->getResourceType()->getId() == $this->binderType->getId()) {
                // Replace by document
                $courseBinder = $this->resourceManager->getResourceFromNode($courseNode);

                $courseNode->setResourceType($this->documentType);
                $courseNode->setMimeType("custom/sidpt_document");

                $this->om->remove($courseBinder);
            } else {
                $courseDocument = $this->resourceManager->getResourceFromNode($courseNode);
            }
        }
        if (empty($courseDocument)) {
            $courseDocument = new Document();
            $courseDocument->setResourceNode($courseNode);
        }
        $courseDocument->setName($course);
        $this->addOrUpdateResourceListWidget($courseDocument, $courseNode, "Modules");
        $this->om->persist($courseDocument);
        $this->om->persist($courseNode);
        $this->om->flush();

        // Get or create the the course tag (on the plateforme)
        $courseTag = $this->tagManager->getOnePlatformTagByName($course, $curriculumTag);
        if (empty($courseTag)) {
            $courseTag = new Tag();
            $courseTag->setName($course);
            $courseTag->setParent($curriculumTag);
            $this->om->persist($courseTag);
        }
        $this->tagManager->tagData(
            ['Content level/Course', $courseTag->getPath()],
            [ 0 => [
                'id'=> $courseNode->getUuid(),
                'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
                'name' => "{$curriculum}/{$course}"
            ]]
        );
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
        $moduleDocument = null;
        if (empty($moduleNode)) {
            $moduleNode = new ResourceNode();
            $moduleNode->setName($module);
            $moduleNode->setWorkspace($workspace);
            $moduleNode->setParent($curriculumNode);
            $moduleNode->setCreator($user);

            $moduleNode->setResourceType($this->documentType);
            $moduleNode->setMimeType("custom/sidpt_document");
            $this->om->persist($moduleNode);
        } else {
            // check if resource is binder
            if ($moduleNode->getResourceType()->getId() == $this->binderType->getId()) {
                // Replace by document
                $moduleBinder = $this->resourceManager->getResourceFromNode($moduleNode);

                $moduleNode->setResourceType($this->documentType);
                $moduleNode->setMimeType("custom/sidpt_document");

                $this->om->remove($moduleBinder);
            } else {
                $moduleDocument = $this->resourceManager->getResourceFromNode($moduleNode);
            }
        }
        if (empty($moduleDocument)) {
            $moduleDocument = new Document();
            $moduleDocument->setResourceNode($moduleNode);
        }
        $moduleDocument->setName($module);
        $this->addOrUpdateResourceListWidget($moduleDocument, $moduleNode, "Learning units");
        $this->om->persist($moduleDocument);
        $this->om->persist($moduleNode);
        $this->om->flush();

        // Get or create the the module tag (plateforme tags)
        $moduleTag = $this->tagManager->getOnePlatformTagByName($module, $courseTag);
        if (empty($moduleTag)) {
            $moduleTag = new Tag();
            $moduleTag->setName($module);
            $moduleTag->setParent($courseTag);
            $this->om->persist($moduleTag);
        }
        $this->tagManager->tagData(
            ['Content level/Module', $moduleTag->getPath()],
            [ 0 => [
                'id'=> $moduleNode->getUuid(),
                'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
                'name' => "{$curriculum}/{$course}/{$module}"
            ]]
        );
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
                    <span style="color: #ff0000;"><strong>Author, please fill this section</strong></span>
                HTML;

        if (empty($learningUnitNode)) {
            $learningUnitNode = new ResourceNode();
            $learningUnitNode->setName($learningUnit);
            $learningUnitNode->setWorkspace($workspace);
            $learningUnitNode->setResourceType($this->documentType);
            $learningUnitNode->setParent($moduleNode);
            $learningUnitNode->setCreator($user);
            $learningUnitNode->setMimeType("custom/sidpt_document");
            $this->om->persist($learningUnitNode);

            $learningUnitDocument = new Document();
            $learningUnitDocument->setResourceNode($learningUnitNode);
        } else {
            $learningUnitDocument = $this->resourceManager
                ->getResourceFromNode($learningUnitNode);
        }

        $learningUnitDocument->setName($learningUnit);
        $learningUnitDocument->setShowOverview(true);
        $learningUnitDocument->setWidgetsPagination(true);

        $learningUnitDocument->setOverviewMessage(
            <<<HTML
<table class="table table-striped table-hover table-condensed data-table" style="height: 133px; width: 100%; border-collapse: collapse; margin-left: auto; margin-right: auto;" border="1" cellspacing="5px" cellpadding="20px">
<tbody>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">{trans('Learning unit','clarodoc')}</td>
<td class="text-left string-cell" style="width: 50%; height: 19px;"><a id="{{ resource.resourceNode.slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.slug}}">{{ resource.resourceNode.name }}</a></td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">{trans('Module','clarodoc')}</td>
<td class="text-left string-cell" style="width: 50%; height: 19px;"><a id="{{ resource.resourceNode.path[-2].slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-2].slug}}">{{ resource.resourceNode.path[-2].name }}</a></td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">{trans('Course','clarodoc')}</td>
<td class="text-left string-cell" style="width: 50%; height: 19px;"><a id="{{ resource.resourceNode.path[-3].slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-3].slug}}">{{ resource.resourceNode.path[-3].name }}</a></td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">{trans('Who is it for?','clarodoc')}</td>
<td style="width: 50%; height: 19px;">{{#resource.resourceNode.tags["professional-profile"]}}{{childrenNames}}{{/resource.resourceNode.tags["professional-profile"]}}</td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">{trans('What is included?','clarodoc')}</td>
<td style="width: 50%; height: 19px;">{{#resource.resourceNode.tags["included-resource-type"]}}{{childrenNames}}{{/resource.resourceNode.tags["included-resource-type"]}}</td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">{trans('How long will it take?','clarodoc')}</td>
<td style="width: 50%; height: 19px;">{{#resource.resourceNode.tags["time-frame"]}}{{childrenNames}}{{/resource.resourceNode.tags["time-frame"]}}</td>
</tr>
<tr style="height: 19px;">
<td style="width: 50%; height: 19px;">{trans('Last updated','clarodoc')}</td>
<td style="width: 50%; height: 19px;">{{#resource.resourceNode.meta.updated}}{{formatDate}}{{/resource.resourceNode.meta.updated}}</td>
</tr>
</tbody>
</table>
HTML
        );

        $learningUnitDocument->setShowDescription(true);
        $learningUnitDocument->setDisclaimer(
            <<<HTML
<p id="disclaimer-start">{{#resource.resourceNode.tags["disclaimer"] }}</p>
<h3>{trans('Disclaimer','clarodoc')}</h3>
<p class="p1">{trans('This learning unit contains images that may not be accessible to some learners. This content is used to support learning. Whenever possible the information presented in the images is explained in the text.','clarodoc')}</p>
<p>{{/resource.resourceNode.tags["disclaimer"] }}</p>
HTML
        );

        // updated description template
        $learningUnitDocument->setDescriptionTitle(
            <<<HTML
<h3>{trans('Learning outcomes','clarodoc')}</h3>
HTML
        );
        $documentNode->setDescription($learningOutcomeContent);

        $user = $learningUnitNode->getCreator();
        $requiredKnowledgeNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Required knowledge",
            $this->directoryType,
            false
        );



        $learningUnitDocument->setRequiredResourceNodeTreeRoot($requiredKnowledgeNode);


        $this->om->persist($learningUnitNode);
        $this->om->persist($learningUnitDocument);
        $this->om->flush();

        // Get or create the the module tag (plateforme tags)
        $learningUnitTag = $this->tagManager->getOnePlatformTagByName($learningUnit, $moduleTag);
        if (empty($learningUnitTag)) {
            $learningUnitTag = new Tag();
            $learningUnitTag->setName($learningUnit);
            $learningUnitTag->setParent($moduleTag);
            $this->om->persist($learningUnitTag);
        }
        $this->tagManager->tagData(
            ['Content level/Learning unit', $learningUnitTag->getPath()],
            [ 0 => [
                'id'=> $learningUnitNode->getUuid(),
                'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
                'name' => "{$curriculum}/{$course}/{$module}/${learningUnit}"
            ]]
        );
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

    public function addOrUpdateDocumentSubObject(
        $user,
        $documentNode,
        $subnodeName,
        $resourceType,
        $withWidget = true
    ) {
        $document = $this->resourceManager->getResourceFromNode($documentNode);
        $subNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => $subnodeName,
                'parent' => $documentNode->getId(),
                'resourceType' => $resourceType->getId(),
            ]
        );
        if (empty($subNode)) {
            $subNode = new ResourceNode();
            $subNode->setName($subnodeName);
            $subNode->setWorkspace($documentNode->getWorkspace());
            $subNode->setResourceType($resourceType);
            $subNode->setParent($documentNode);
            $subNode->setCreator($user);
            $subNode->setMimeType("custom/" . $resourceType->getName());
            $this->om->persist($subNode);

            $resourceclass = $resourceType->getClass();
            $subResource = new $resourceclass();
            $subResource->setResourceNode($subNode);
            $subResource->setName($subnodeName);

            if ($resourceType->getName() == "ujm_exercise") {
                if ($subnodeName == "Practice") {
                    $subResource->setType(ExerciseType::CONCEPTUALIZATION);
                    $subResource->setScoreRule(json_encode(["type" => "none"]));
                } else if ($subnodeName == "Assessment") {
                    $subResource->setType(ExerciseType::SUMMATIVE);
                    $subResource->setScoreRule(json_encode(["type" => "sum"]));
                }
            }

            $this->om->persist($subResource);

            $this->om->persist($document);

            if ($withWidget) {
                $this->addResourceWidget($document, $subNode, $subnodeName);
            }
        } else {
            // Update the document or node
            // Update the underlying resource
            $subResource = $this->resourceManager->getResourceFromNode($subNode);
            if ($subResource->getMimeType() == "custom/ujm_exercise") {
                if ($subResource->getType() == ExerciseType::SUMMATIVE) {
                    if (empty($subResource->getScoreRule())) {
                        $subResource->setScoreRule(
                            json_encode(["type" => "none"])
                        );
                    }
                } else if ($subResource->getType() == ExerciseType::SUMMATIVE) {
                    if (empty($subResource->getScoreRule())) {
                        $subResource->setScoreRule(
                            json_encode(["type" => "sum"])
                        );
                    }
                }
            }
            // update the widget
            $subNodeWidgets = $this->resourceWidgetsRepo->findBy(
                [
                    'resourceNode' => $subNode->getId(),
                ]
            );
            if (!empty($subNodeWidgets)) {
                // widget was found
                foreach ($subNodeWidgets as $widget) {
                    $widget->setShowResourceHeader(false);
                    $instance = $widget->getWidgetInstance();
                    $container = $instance->getContainer();
                    if (!$withWidget) {
                        // widget is no more requested
                        // remove the widget container
                        $this->om->remove($container);
                        $this->om->remove($instance);
                        $this->om->remove($widget);
                    } else {
                        $containerConfig = $container->getWidgetContainerConfigs()->first();
                        $containerConfig->setName($subnodeName);

                        $this->om->persist($widget);
                    }
                }
            } elseif ($withWidget) {
                // subnode is alledgedly used in a widget but was not found
                // So we add it
                $this->addResourceWidget($document, $subNode, $subnodeName);
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
                [0 => [
                    "property" => "parent",
                    "value" => [
                        "id" => $parentNode->getUuid(),
                        "name" => $parentNode->getName(),
                    ],
                    "locked" => true,
                ],
                ]
            );

            $widget->setDisplay("table");
            $widget->setActions(false);
            $widget->setCount(true);
            $widget->setDisplayedColumns(["name", "meta.description"]);
            if ($name == "Learning units") {
                // update widget to display two columns
                // - the name column should be labeled "Select a learning unit" with a
                // - the meta.description should be title "Learning outcomes"
                $widget->setColumnsCustomization(
                    [
                        "name" => [
                            "label" => "Select a learning unit",
                            "translateLabel" => true,
                            "translationDomain" => 'clarodoc',
                        ],
                        "meta.description" => [
                            "label" => "Learning outcomes",
                            "translateLabel" => true,
                            "translationDomain" => 'clarodoc',
                        ],
                    ]
                );
            } elseif ($name == "Modules") {
                // update widget to display two columns
                // - the name column should be labeled "Select a module" with a
                // - the meta.description title should be removed
                $widget->setColumnsCustomization(
                    [
                        "name" => [
                            "label" => "Select a module",
                            "translateLabel" => true,
                            "translationDomain" => 'clarodoc',
                        ],
                        "meta.description" => [
                            "hideLabel" => true,
                        ],
                    ]
                );
            } else {
                // possibly course list, but might be done manually
            }

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
                    "widgetInstance" => $instance->getId(),
                ]
            );

            $widget->setFilters(
                [0 => [
                    "property" => "parent",
                    "value" => [
                        "id" => $parentNode->getUuid(),
                        "name" => $parentNode->getName(),
                    ],
                    "locked" => true,
                ],
                ]
            );
            $widget->setDisplay("table");
            $widget->setActions(false);
            $widget->setCount(true);
            $widget->setDisplayedColumns(["name", "meta.description"]);

            $containerConfig->setName($name);

            if ($name == "Learning units") {
                // update widget to display two columns
                // - the name column should be labeled "Select a learning unit" with a
                // - the meta.description should be title "Learning outcomes"
                $widget->setColumnsCustomization(
                    [
                        "name" => [
                            "label" => "Select a learning unit",
                            "translateLabel" => true,
                            "translationDomain" => 'clarodoc',
                        ],
                        "meta.description" => [
                            "label" => "Learning outcomes",
                            "translateLabel" => true,
                            "translationDomain" => 'clarodoc',
                        ],
                    ]
                );
            } elseif ($name == "Modules") {
                // update widget to display two columns
                // - the name column should be labeled "Select a module" with a
                // - the meta.description title should be removed
                $widget->setColumnsCustomization(
                    [
                        "name" => [
                            "label" => "Select a module",
                            "translateLabel" => true,
                            "translationDomain" => 'clarodoc',
                        ],
                        "meta.description" => [
                            "hideLabel" => true,
                        ],
                    ]
                );
            } else {
                // possibly course list, but might be done manually
            }

            $this->om->persist($widget);
            $this->om->persist($containerConfig);
        }
        $this->om->persist($document);
        $this->om->flush();
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
        $this->om->persist($newWidget);

        $newWidgetInstance = new WidgetInstance();
        $newWidgetInstance->setWidget($this->resourceWidgetType);
        $newWidgetInstance->setDataSource($this->resourceDataSource);
        $this->om->persist($newWidgetInstance);
        $newWidget->setWidgetInstance($newWidgetInstance);

        $newWidgetInstanceConfig = new WidgetInstanceConfig();
        $newWidgetInstanceConfig->setType("resource");
        $newWidgetInstanceConfig->setWidgetInstance($newWidgetInstance);
        $this->om->persist($newWidgetInstanceConfig);

        $newWidgetContainer = new WidgetContainer();
        $newWidgetContainer->addInstance($newWidgetInstance);
        $newWidgetInstance->setContainer($newWidgetContainer);
        $this->om->persist($newWidgetContainer);

        $newWidgetContainerConfig = new WidgetContainerConfig();
        $newWidgetContainerConfig->setName($name);
        $newWidgetContainerConfig->setBackgroundType("color");
        $newWidgetContainerConfig->setBackground("#ffffff");
        $newWidgetContainerConfig->setPosition(0);
        $newWidgetContainerConfig->setLayout(array(1));
        $newWidgetContainerConfig->setWidgetContainer($newWidgetContainer);
        $this->om->persist($newWidgetContainerConfig);

        $document->addWidgetContainer($newWidgetContainer);
        $this->om->persist($document);
    }



    /**
     *
     */
    public function getClass()
    {
        return ResourceNode::class;
    }

    /**
     *
     */
    public function getAction()
    {
        return ['content', 'generate_ipip_content'];
        //return ['workspace', 'generate_ipip_content'];
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
