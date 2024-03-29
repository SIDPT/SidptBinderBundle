<?php

namespace Sidpt\BinderBundle\API\Manager;


use Claroline\AppBundle\API\Crud;
use Claroline\AppBundle\API\FinderProvider;
use Claroline\AppBundle\API\SerializerProvider;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\DataSource;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Claroline\CoreBundle\Entity\Resource\ResourceType;
use Claroline\CoreBundle\Entity\Widget\Type\AbstractWidget;
use Claroline\CoreBundle\Entity\Widget\Type\SimpleWidget;
use Claroline\CoreBundle\Entity\Widget\Type\ListWidget;
use Claroline\CoreBundle\Entity\Widget\Type\ResourceWidget;
use Claroline\CoreBundle\Entity\Widget\Widget;
use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Claroline\CoreBundle\Entity\Widget\WidgetContainerConfig;
use Claroline\CoreBundle\Entity\Widget\WidgetInstance;

// entities
use Claroline\CoreBundle\Entity\Widget\WidgetInstanceConfig;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Claroline\CoreBundle\Manager\ContentTranslationManager;
use Claroline\CoreBundle\Manager\Organization\OrganizationManager;
use Claroline\CoreBundle\Manager\ResourceManager;
use Claroline\CoreBundle\Manager\RoleManager;
use Claroline\CoreBundle\Manager\Workspace\WorkspaceManager;
use Claroline\HomeBundle\Entity\HomeTab;
use Claroline\TagBundle\Manager\TagManager;
use Sidpt\BinderBundle\Entity\Binder;
use Sidpt\BinderBundle\Entity\Document;

use UJM\ExoBundle\Library\Options\ExerciseType;

// logging for debug
use Claroline\AppBundle\Log\LoggableTrait;
use Psr\Log\LoggerAwareInterface;


/**
 * Class to manipulate a document widget
 */
class WidgetData {
    
    /**
     * Widget object
     *
     * @var null|AbstractWidget
     */
    public $widget = null;

    
    /**
     * Container of the widget (= section of a document)
     *
     * @var null|WidgetContainer
     */
    public $container = null;

    /**
     * Instance of the widget (= column of a container)
     *
     * @var null|WidgetInstance
     */
    public $instance = null;
}

class DocumentManager implements LoggerAwareInterface
{
    use LoggableTrait;

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

    // Class variables for execute function (hoping it can be help performances)
    private $workspaceRepo;
    private $resourceNodeRepo;
    private $homeTabsRepo;

    private $simpleWidgetsRepo;
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
    private $simpleWidgetType;
    private $resourceWidgetType;
    private $listWidgetType;

    private $nodeSeralizer;
    private $documentSeralizer;

    // Tags hierarchy
    private $contentLevel;
    private $professionnalProfile;
    private $estimatedTime;
    private $includedResources;

    private $translations;

    const BANNER_IMAGES_LINK = [
        'Activity' => 'bundles/sidptbinder/images/lu/Activity.jpeg', // Activity banner image
        'References' => 'bundles/sidptbinder/images/lu/References.jpeg',  // References banner image
        'Theory' => 'bundles/sidptbinder/images/lu/Theory.jpeg', // Theory banner image
    ];


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
        ContentTranslationManager $translations
    ) {
        $this->translations = $translations;
        $this->om = $om;
        $this->crud = $crud;
        $this->serializer = $serializer;
        $this->finder = $finder;
        $this->organizationManager = $organizationManager;
        $this->tagManager = $tagManager;
        $this->roleManager = $roleManager;
        $this->workspaceManager = $workspaceManager;
        $this->resourceManager = $resourceManager;

        $this->workspaceRepo = $this->om->getRepository(Workspace::class);
        $this->resourceNodeRepo = $this->om->getRepository(ResourceNode::class);
        $this->homeTabsRepo = $this->om->getRepository(HomeTab::class);

        $this->simpleWidgetsRepo = $this->om->getRepository(SimpleWidget::class);
        $this->resourceWidgetsRepo = $this->om->getRepository(ResourceWidget::class);
        $this->listWidgetsRepo = $this->om->getRepository(ListWidget::class);

        $this->nodeSeralizer = $this->serializer->get(ResourceNode::class);
        $this->documentSeralizer = $this->serializer->get(Document::class);
    }

    private function doctrineLoad()
    {
        $widgetsTypeRepo = $this->om->getRepository(Widget::class);
        $dataSourceRepo = $this->om->getRepository(DataSource::class);
        $typesRepo = $this->om->getRepository(ResourceType::class);

        $this->binderType = $typesRepo->findOneBy(
            ['name' => 'sidpt_binder']
        );
        $this->documentType = $typesRepo->findOneBy(
            ['name' => 'sidpt_document']
        );

        $this->directoryType = $typesRepo->findOneBy(
            ['name' => 'directory']
        );

        $this->lessonType = $typesRepo->findOneBy(
            ['name' => 'icap_lesson']
        );

        $this->exerciseType = $typesRepo->findOneBy(
            ['name' => 'ujm_exercise']
        );

        $this->textType = $typesRepo->findOneBy(
            ['name' => 'text']
        );

        $this->resourceDataSource = $dataSourceRepo->findOneBy(
            ['name' => 'resource']
        );
        $this->resourcesListDataSource = $dataSourceRepo->findOneBy(
            ['name' => 'resources']
        );

        $this->simpleWidgetType = $widgetsTypeRepo->findOneBy(
            ['name' => 'simple']
        );

        $this->resourceWidgetType = $widgetsTypeRepo->findOneBy(
            ['name' => 'resource']
        );
        $this->listWidgetType = $widgetsTypeRepo->findOneBy(
            ['name' => 'list']
        );
    }



    public function configureAsLearningUnit(
        Document $learningUnitDocument,
        bool $flush = true
    ) {
        $this->doctrineLoad();
        $learningUnitNode = $learningUnitDocument->getResourceNode();
        $learningUnitDocument->setShowOverview(true);
        $learningUnitDocument->setWidgetsPagination(true);
        $learningUnitDocument->setShowDescription(true);

        $learningUnitDocument->setDisclaimer(null);
        $learningUnitDocument->setOverviewMessage(null);
        $learningUnitDocument->setDescriptionTitle(null);

        $description = $learningUnitNode->getDescription();
        $learningOutcomeContent = $description;
        // update previous description
        if (!empty($description)) {
            // original template
            $searchedOutcome = explode(
                "<h3>Learning outcomes</h3>",
                $description
            );
            if (count($searchedOutcome) > 1) {
                $docIsLU = true;
                $learningOutcomeContent = explode(
                    "<p>{{#resource.resourceNode.tags[\"Disclaimer\"] }}</p>",
                    $searchedOutcome[1]
                )[0];
                $learningOutcomeContent = trim($learningOutcomeContent);
                $learningOutcomeContent = substr(
                    $learningOutcomeContent,
                    0,
                    strlen($learningOutcomeContent)
                );
            } else {
                $searchedOutcome = explode(
                    "<h3>Learning outcome</h3>",
                    $description
                );
                if (count($searchedOutcome) > 1) {
                    $docIsLU = true;
                    $learningOutcomeContent = explode(
                        "<p>{{#resource.resourceNode.tags[\"Disclaimer\"] }}</p>",
                        $searchedOutcome[1]
                    )[0];
                    $learningOutcomeContent = trim($learningOutcomeContent);
                    $learningOutcomeContent = substr(
                        $learningOutcomeContent,
                        0,
                        strlen($learningOutcomeContent)
                    );
                } else {
                    // template v2
                    // (translations)
                    $searchedOutcome = explode(
                        "<h3>{trans('Learning outcomes','clarodoc')}</h3>",
                        $description
                    );
                    if (count($searchedOutcome) > 1) {
                        $docIsLU = true;
                        $learningOutcomeContent = explode(
                            "<p id=\"disclaimer-start\">",
                            $searchedOutcome[1]
                        )[0];
                        $learningOutcomeContent = trim($learningOutcomeContent);
                    }
                }
            }
        }

        $learningUnitNode->setDescription($learningOutcomeContent);

        // Reset containers list
        $learningUnitDocument->getWidgetContainers()->clear();
        $this->om->persist($learningUnitNode);
        $this->om->persist($learningUnitDocument);
        $this->om->flush();


        $user = $learningUnitNode->getCreator();
        $requiredKnowledgeNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Required knowledge",
            $this->directoryType
        );
        $learningUnitDocument->setRequiredResourceNodeTreeRoot($requiredKnowledgeNode);

        // - a practice exercise:
        $practiceNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Practice",
            $this->exerciseType
        );
        $this->addOrUpdateResourceWidget($learningUnitDocument, $practiceNode, "Practice", "<p>{trans('Determine what you already know.','clarodoc')}</p>");
        // - A theroy lesson
        $theoryNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Theory",
            $this->lessonType
        );
        $this->addOrUpdateResourceWidget($learningUnitDocument, $theoryNode, "Theory", "<p>{trans('Read and learn about the topic.','clarodoc')}</p>");

        // - An assessment exercice
        $assessmentNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Assessment",
            $this->exerciseType
        );
        $this->addOrUpdateResourceWidget($learningUnitDocument, $assessmentNode, "Assessment", "<p>{trans('Evaluate what you have learned.','clarodoc')}</p>");


        // - An activity text
        $activityNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "Activity",
            $this->textType
        );
        $this->addOrUpdateResourceWidget($learningUnitDocument, $activityNode, "Activity", "<p>{trans('Put everything you have learned into practice.','clarodoc')}</p>");

        // - A references document
        $referencesNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $learningUnitNode,
            "References",
            $this->documentType
        );
        $this->addOrUpdateResourceWidget(
            $learningUnitDocument,
            $referencesNode,
            "References",
            "<div>
        <p>{trans('You have completed the learning unit.','clarodoc')}</p>
        <p>{trans('Consult resources and find related learning material on the platform.','clarodoc')}</p>
    </div>"
        );

        $this->om->persist($learningUnitNode);
        $this->om->persist($learningUnitDocument);
        $this->om->flush();

        $referencesDocument = $this->resourceManager->getResourceFromNode($referencesNode);
        // Delete all containers
        $referencesDocument->getWidgetContainers()->clear();
        $this->om->persist($referencesDocument);
        $this->om->flush();
        // The reference document contains 2 sections :
        // - an external reference textual section
        $externalReferencesNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $referencesNode,
            "External references",
            $this->textType
        );
        $this->addOrUpdateResourceWidget($referencesDocument, $externalReferencesNode, "External references");

        // - an internal reference folder, to store a hierarchy of shortcuts
        $internalReferencesNode = $this->addOrUpdateDocumentSubObject(
            $user,
            $referencesNode,
            "IPIP references",
            $this->directoryType
        );
        
        // reset actions capabilities on the directory
        // 20220902 - replace IPIP References resource widget by a List widget with parent filter set with the IPIP References folder
        $this->addOrUpdateResourceListWidget(
            $referencesDocument,
            $internalReferencesNode,
            "IPIP references",
            true
        );

        $this->om->persist($referencesDocument);
        $this->om->persist($learningUnitNode);
        $this->om->persist($learningUnitDocument);
        if ($flush) {
            $this->om->flush();
        }

        // TODO build tag hierarchy
        // Get or create the the module tag (plateforme tags)
        // $learningUnitTag = $this->tagManager->getOnePlatformTagByName($learningUnitNode->getName(), $moduleTag);
        // if (empty($learningUnitTag)) {
        //     $learningUnitTag = new Tag();
        //     $learningUnitTag->setName($learningUnit);
        //     $learningUnitTag->setParent($moduleTag);
        //     $this->om->persist($learningUnitTag);
        // }
        // $this->tagManager->tagData(
        //     ['Content level/Learning unit', $learningUnitTag->getPath()],
        //     [ 0 => [
        //         'id'=> $learningUnitNode->getUuid(),
        //         'class' => "Claroline\CoreBundle\Entity\Resource\ResourceNode",
        //         'name' => "{$curriculum}/{$course}/{$module}/${learningUnit}"
        //     ]]
        // );
        // $this->om->flush();

    }

    public function configureAsModule(
        Document $moduleDocument,
        bool $flush = true
    ) {
        $this->doctrineLoad();
        // set description
        // set learning unit resource list
        $moduleNode = $moduleDocument->getResourceNode();
        $moduleDocument->setShowOverview(false);
        $moduleDocument->setWidgetsPagination(false);

        // reset widgets from document
        //$this->documentSeralizer->deserializeWidgets([],$moduleDocument);
        $moduleDocument->getWidgetContainers()->clear();
        $this->om->persist($moduleDocument);
        $this->om->flush();

        $this->addOrUpdateResourceListWidget($moduleDocument, $moduleNode, "Learning units");

        $this->om->persist($moduleNode);
        $this->om->persist($moduleDocument);
        if ($flush) {
            $this->om->flush();
        }
    }

    public function configureAsCourse(
        Document $courseDocument,
        bool $flush = true
    ) {
        $this->doctrineLoad();
        //  set description
        // set modules resources list
        $courseNode = $courseDocument->getResourceNode();
        $courseDocument->setShowOverview(false);
        $courseDocument->setWidgetsPagination(false);

        // remove widgets from document
        //$this->documentSeralizer->deserializeWidgets([],$courseDocument);
        $courseDocument->getWidgetContainers()->clear();
        $this->om->persist($courseDocument);
        $this->om->flush();

        // recreate the widget
        $this->addOrUpdateResourceListWidget($courseDocument, $courseNode, "Modules");

        $courseNode->setDescription(<<<HTML
    <ul>{{#resource.resourceNode.children}}
    <li><a id="{{ slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{workspace.slug}}/resources/{{slug}}">{{ name }}</a></li>
    {{/resource.resourceNode.children}}</ul>
    HTML);

        $this->om->persist($courseNode);
        $this->om->persist($courseDocument);
        if ($flush) {
            $this->om->flush();
        }
    }

    /**
     * [addOrUpdateDocumentSubObject description]
     * @param  [type]       $user                       [description]
     * @param  [type]       $documentNode               [description]
     * @param  [type]       $subnodeName                [description]
     * @param  [type]       $resourceType               [description]
     * @return ResourceNode               the newly created resource node
     */
    public function addOrUpdateDocumentSubObject(
        $user,
        $documentNode,
        $subnodeName,
        $resourceType
    ): ResourceNode {
        $document = $this->resourceManager->getResourceFromNode($documentNode);
        $subNode = $this->resourceNodeRepo->findOneBy(
            [
                'name' => $subnodeName,
                'parent' => $documentNode->getId(),
                'resourceType' => $resourceType->getId(),
                'active' => true
            ]
        );
        $subResource = null;
        // Create node if not present, else retrieve subresource
        if (empty($subNode)) {
            $subNode = new ResourceNode();
            $subNode->setName($subnodeName);
            $subNode->setWorkspace($documentNode->getWorkspace());
            $subNode->setResourceType($resourceType);
            $subNode->setParent($documentNode);
            $subNode->setCreator($user);
            $subNode->setMimeType("custom/" . $resourceType->getName());

            $this->om->persist($subNode);
        } else {
            $subResource = $this->resourceManager->getResourceFromNode($subNode);
        }
        // create new subresource if not present
        if (empty($subResource)) {
            $resourceclass = $resourceType->getClass();
            $subResource = new $resourceclass();

            $subResource->setResourceNode($subNode);
            $subResource->setName($subnodeName);
            $this->om->persist($subResource);
        }
        // update subresource
        if ($resourceType->getName() == "ujm_exercise") {
            $subResource->setShowOverview(false);
            $subResource->setProgressionDisplayed(false);
            $subResource->setShowEndConfirm(false);
            if ($subnodeName == "Practice") {
                $subResource->setType(ExerciseType::CONCEPTUALIZATION);
                $subResource->setScoreRule(json_encode(["type" => "none"]));
            } else if ($subnodeName == "Assessment") {
                $subResource->setType(ExerciseType::SUMMATIVE);
                $subResource->setScoreRule(json_encode(["type" => "sum"]));
            }
        } elseif ($resourceType->getName() == "icap_lesson") {
            $link = self::BANNER_IMAGES_LINK["Theory"];
            // set poster in overview message
            $subResource->setDescription(<<<HTML
          <img src="$link" class="img-responsive" style="max-height:300px;"/>
        HTML);
            $subResource->searchIsAllowed(false);
        } elseif ($resourceType->getName() == "directory") {
            // 2022/09/05 : table view
            $subResource->setDisplay("table");
            $subResource->setActions(true);
            $subResource->setCount(true);
            $subResource->setAvailableFilters([]);
            $subResource->setDisplayedColumns(["name","absolutePath"]);
            $subResource->setColumnsCustomization(
                [
                    "absolutePath" => [
                        "hideLabel" => true
                    ]
                ]
            );
        }
        $this->om->persist($subResource);
        $this->om->persist($document);

        //$this->om->flush();
        return $subNode;
    }

    /**
     * [addOrUpdateResourceListWidget description]
     * @param [type] $document     [description]
     * @param [type] $resourceNode [description]
     */
    public function addOrUpdateResourceListWidget(
        Document $document,
        ResourceNode $parentNode,
        $name = null,
        $showName = false
    ) {
        $widget = null;
        $widgetFound = false;
        $instance = null;
        $container = null;
        $containerConfig = null;
        $instanceConfig = null;
        // Check if document has container
        if (!$document->getWidgetContainers()->isEmpty()) {
            // Search for a list widget with matching parentnode in the document widgets container
            foreach ($document->getWidgetContainers() as $key => $currentContainer) {
                foreach ($currentContainer->getInstances() as $key => $currentInstance) {
                    if($this->listWidgetType == $currentInstance->getWidget()){
                        $widget = $this->listWidgetsRepo->findOneBy(
                            [
                                "widgetInstance" => $currentInstance->getId(),
                            ]
                        );
                        if ($widget) {
                            $filters = $widget->getFilters();
                            foreach ($filters as $key => $filter) {
                                if (isset($filter["property"])
                                    && $filter["property"] === "parent"
                                    && isset($filter["value"]["id"])
                                    && $filter["value"]["id"] === $parentNode->getUuid()
                                ) {
                                    // We found the searched widget, its instance and its container
                                    $instance = $currentInstance;
                                    $instanceConfig = $currentInstance->getWidgetInstanceConfigs()->first();
                                    $container = $currentContainer;
                                    $containerConfig = $currentContainer->getWidgetContainerConfigs()->first();
                                    $widgetFound = true;
                                    break 3;
                                }
                            }
                            // Not broke, reset widget and keep looking
                            $widget = null;
                        }
                    }
                }
            }
        }
        if(empty($widget)){ // No matching widget found in any container, prepare a new container with the widget
            $widget = new ListWidget();
            $instance = new WidgetInstance();
            $instance->setWidget($this->listWidgetType);
            $instance->setDataSource($this->resourcesListDataSource);
            $widget->setWidgetInstance($instance);
            $instanceConfig = new WidgetInstanceConfig();
            $instanceConfig->setType("list");
            $instanceConfig->setWidgetInstance($instance);
            $container = new WidgetContainer();
            $container->addInstance($instance);
            $instance->setContainer($container);
            $containerConfig = new WidgetContainerConfig();
            if ($showName) {
                $containerConfig->setName($name);
            }
            $containerConfig->setBackgroundType("color");
            $containerConfig->setBackground("#ffffff");
            $containerConfig->setPosition(0);
            $containerConfig->setLayout(array(1));
            $containerConfig->setWidgetContainer($container);
        }
        $widget->setFilters(
            [
                0 => [
                    "property" => "parent",
                    "value" => [
                        "id" => $parentNode->getUuid(),
                        "name" => $parentNode->getName(),
                    ],
                    "locked" => true,
                ],
                1 => [
                    "property" => "published",
                    "value" => true,
                    "locked" => true,
                ]
            ]
        );
        // Default display
        $widget->setDisplay("table");
        $widget->setActions(false);
        $widget->setCount(true);
        $widget->setDisplayedColumns(["name", "meta.description"]);
        // Set section title here
        if ($showName) {
            $containerConfig->setName($name);
        } else {
            $containerConfig->setName(null);
        }
        
        if ($name == "Learning units") { // For learning units listing in modules
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
        } elseif ($name == "Modules") { // For Modules listing in courses
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
        } elseif ($name == "IPIP references"){ // For ipip reference section in reference document
            $widget->setDisplay("table");
            $widget->setActions(false);
            $widget->setCount(false);
            $widget->setDisplayedColumns(["absolutePath"]);
            $widget->setColumnsCustomization(
                [
                    "absolutePath" => [
                        "hideLabel" => true
                    ]
                ]
            );
        } 
        else {
            // possibly course list in curriculum, 
            // but might be done manually as there very few pages
        }
        // Save the changes
        $this->om->persist($widget);
        $this->om->persist($instance);
        $this->om->persist($container);
        // if widget/instance/container are new,
        if(!$widgetFound){
            // add the container to document
            $document->addWidgetContainer($container);
        }
        
        $this->om->persist($document);
        //$this->om->flush();
    }

    
    /**
     * Search a widget that uses a dedicated resource node within a document
     *
     * @param Document $document
     * @param ResourceNode $resourceNode
     * @param Widget $widgetType resource or list widget type, null as default for resource widget
     * @return WidgetData|null
     */
    public function searchWidgetUsingResource(
        Document $document,
        ResourceNode $resourceNode,
        Widget $widgetType = null
    ) : ?WidgetData {
        
        $data = new WidgetData();

        $widgetSections = $document->getWidgetContainers()->toArray();
        foreach ($widgetSections as $section) {
            // get container instances
            $currentInstances = $section->getInstances()->toArray();
            foreach ($currentInstances as $key => $instance) {
                // check for an existing resource widget pointing to the
                // resource node in this instance
                $widget = null;
                if( $this->listWidgetType == $widgetType
                    && $this->listWidgetType == $instance->getWidget()
                ){
                    $tempWidget = $this->listWidgetsRepo->findOneBy(
                        [
                            "widgetInstance" => $instance->getId(),
                        ]
                    );
                    if (!empty($tempWidget)) {
                        $filters = $tempWidget->getFilters();
                        foreach ($filters as $key => $filter) {
                            if (isset($filter["property"])
                                && $filter["property"] === "parent"
                                && isset($filter["value"]["id"])
                                && $filter["value"]["id"] === $resourceNode->getUuid()
                            ) {
                                $widget = $tempWidget;
                                break;
                            }
                        }
                    }
                } else if ($this->resourceWidgetType == $instance->getWidget()) {
                    // default search for Resource
                    $widget = $this->resourceWidgetsRepo->findOneBy(
                        [
                            "widgetInstance" => $instance->getId(),
                            "resourceNode" => $resourceNode->getId()
    
                        ]
                    );
                }
                if (!empty($widget)) {
                    $data->widget = $widget;
                    $data->instance = $instance;
                    $data->container = $section;
                    return $data;
                }
            }
        }
        return null;
    }

    /**
     * Make or update an existing resource widget section
     * @param [type] $document     [description]
     * @param [type] $resourceNode [description]
     */
    public function addOrUpdateResourceWidget(
        $document,
        $resourceNode,
        $name = null,
        $introduction = null,
        $conclusion = null
    ) {
        $bannerText = null;
        if ($name !== "Theory" && array_key_exists($name, self::BANNER_IMAGES_LINK)) {
            $link = self::BANNER_IMAGES_LINK[$name];
            $bannerText = <<<HTML
          <img src="${link}" class="img-responsive" style="max-height:300px;"/>
        HTML;
        }


        $resourceWidget = null;
        $resourceWidgetInstance = null;
        $resourceWidgetInstanceConfig = null;

        $bannerWidget = null;
        $bannerWidgetInstance = null;
        $bannerWidgetInstanceConfig = null;

        $widgetSection = null;
        $widgetSectionConfig = null;

        // Search for an existing widget holding the resource widget in the
        // current document widget sections
        $widgetSections = $document->getWidgetContainers()->toArray();
        foreach ($widgetSections as $section) {
            // get container instances
            $currentInstances = $section->getInstances()->toArray();
            foreach ($currentInstances as $key => $instance) {
                // check for an existing resource widget pointing to the
                // resource node in this instance
                $resourceWidget = $this->resourceWidgetsRepo->findOneBy(
                    [
                        "widgetInstance" => $instance->getId(),
                        "resourceNode" => $resourceNode->getId()

                    ]
                );

                if (!empty($resourceWidget)) {
                    $resourceWidgetInstance = $instance;
                    $resourceWidgetInstanceConfig = $instance->getWidgetInstanceConfigs()->first();
                    //break;
                } elseif (!empty($bannerText)) {
                    // For resource widget that should have a banner,
                    // check if the instance contains one with the wanted banner
                    $bannerWidget = $this->simpleWidgetsRepo->findOneBy(
                        [
                            "widgetInstance" => $instance->getId(),
                            "content" => $bannerText
                        ]
                    );
                    if (!empty($bannerWidget)) {
                        $bannerWidgetInstance = $instance;
                        $bannerWidgetInstanceConfig = $instance->getWidgetInstanceConfigs()->first();
                        //break;
                    }
                }
            }

            if (!empty($resourceWidget)) {
                $widgetSection = $section;
                $widgetSectionConfig = $section->getWidgetContainerConfigs()->first();
                break;
            }
        }

        // widget not found, make a new one
        if (empty($resourceWidget)) {
            // make widget, instance and container
            $resourceWidget = new ResourceWidget();
            $resourceWidget->setResourceNode($resourceNode);
            $resourceWidget->setShowResourceHeader(false);
            $this->om->persist($resourceWidget);

            $resourceWidgetInstance = new WidgetInstance();
            $resourceWidgetInstance->setWidget($this->resourceWidgetType);
            $resourceWidgetInstance->setDataSource($this->resourceDataSource);
            $this->om->persist($resourceWidgetInstance);
            $resourceWidget->setWidgetInstance($resourceWidgetInstance);

            $resourceWidgetInstanceConfig = new WidgetInstanceConfig();
            $resourceWidgetInstanceConfig->setType("resource");
            $resourceWidgetInstanceConfig->setWidgetInstance($resourceWidgetInstance);

            $widgetSection = new WidgetContainer();
            $this->om->persist($widgetSection);

            $widgetSectionConfig = new WidgetContainerConfig();
            $widgetSectionConfig->setWidgetContainer($widgetSection);
            $this->om->persist($widgetSectionConfig);

            $document->addWidgetContainer($widgetSection);
        }

        if (!empty($bannerText)) {

            // empty banner widget, make new one
            if (empty($bannerWidget)) {
                // make the banner widget
                $bannerWidget = new SimpleWidget();
                $bannerWidget->setContent($bannerText);
                $this->om->persist($bannerWidget);

                $bannerWidgetInstance = new WidgetInstance();
                $bannerWidgetInstance->setWidget($this->simpleWidgetType);
                $bannerWidgetInstance->setDataSource(null);
                $this->om->persist($bannerWidgetInstance);
                $bannerWidget->setWidgetInstance($bannerWidgetInstance);

                $bannerWidgetInstanceConfig = new WidgetInstanceConfig();
                $bannerWidgetInstanceConfig->setType("simple");
                $bannerWidgetInstanceConfig->setWidgetInstance($bannerWidgetInstance);

                $this->om->persist($bannerWidgetInstanceConfig);
            }
            $bannerWidget->setContent($bannerText);
            $this->om->persist($bannerWidget);

            $widgetSection->addInstance($bannerWidgetInstance);
            $widgetSection->addInstance($resourceWidgetInstance);
            $widgetSectionConfig->setLayout([1, 2]);

            $bannerWidgetInstanceConfig->setPosition(0);
            $resourceWidgetInstanceConfig->setPosition(1);

            $this->om->persist($bannerWidgetInstanceConfig);
        } else {
            $resourceWidgetInstanceConfig->setPosition(0);
            $widgetSection->addInstance($resourceWidgetInstance);
            $widgetSectionConfig->setLayout(array(1));
        }
        $this->om->persist($resourceWidgetInstanceConfig);

        $widgetSectionConfig->setName($name);
        $widgetSectionConfig->setBackgroundType("color");
        $widgetSectionConfig->setBackground("#ffffff");
        $widgetSectionConfig->setWidgetContainer($widgetSection);
        $widgetSectionConfig->setIntroduction($introduction);
        $widgetSectionConfig->setConclusion($conclusion);
        $this->om->persist($widgetSectionConfig);
        $this->om->persist($widgetSection);

        $document->addWidgetContainer($widgetSection);
        //$this->om->persist($document);
    }
}
