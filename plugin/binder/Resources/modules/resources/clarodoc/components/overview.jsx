import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import isEmpty from 'lodash/isEmpty'

import {trans} from '#/main/app/intl/translation'
import {scrollTo} from '#/main/app/dom/scroll'
import {CALLBACK_BUTTON, CallbackButton} from '#/main/app/buttons'
import {ContentHtmlComponent} from '#/main/app/content/components/html'
import {ResourceOverview} from '#/main/core/resource/components/overview'
import {ContentSummary} from '#/main/app/content/components/summary'
import {Widget} from '#/main/core/widget/player/components/widget'

import {displayDate} from '#/main/app/intl/date'

const getUpdateDate = (resourceNode)=>{
  if(resourceNode && resourceNode.meta && resourceNode.meta.updated){
    return displayDate(resourceNode.meta.updated, false, true)
  }
  return "";
}

const DocumentOverview = (props) => {
    const overviewContent = [];
    overviewContent.push(
        // Added default overview template, description title and disclaimer
        props.overviewMessage ? props.overviewMessage :
`<table
    class="table-hover lu-table"
    style="font-weight:bold;"
    border="1"
    cellspacing="5px"
    cellpadding="20px">
  <tbody>
  <tr>
    <td>{trans('Learning unit','clarodoc')}</td>
    <td>
      <ul>
        <li><b>{trans('Course','clarodoc')}:</b> <a id="{{ resource.resourceNode.path[-3].slug }}" class="default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-3].slug}}">{{ resource.resourceNode.path[-3].name }}</a></li>
        <li><b>{trans('Module','clarodoc')}:</b> <a id="{{ resource.resourceNode.path[-2].slug }}" class="default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-2].slug}}">{{ resource.resourceNode.path[-2].name }}</a></li>
        <li><b>{trans('Learning unit','clarodoc')}:</b> <a id="{{ resource.resourceNode.slug }}" class="default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.slug}}">{{ resource.resourceNode.name }}</a></li>
      </ul>
    </td>
  </tr>
  <tr>
    <td>{trans('Professional profiles','clarodoc')}</td>
    <td>{{#resource.resourceNode.tags["professional-profile"]}}{{childrenNames}}{{/resource.resourceNode.tags["professional-profile"]}}</td>
  </tr>
  <tr>
    <td>{trans('Learning objects','clarodoc')}</td>
    <td>{{#resource.resourceNode.tags["included-resource-type"]}}{{childrenNames}}{{/resource.resourceNode.tags["included-resource-type"]}}</td>
  </tr>
  <tr>
    <td>{trans('Approximate duration','clarodoc')}</td>
    <td>{{#resource.resourceNode.tags["time-frame"]}}{{childrenNames}}{{/resource.resourceNode.tags["time-frame"]}}</td>
  </tr>
  {{ #requirements}}
  <tr>
    <td>{trans('Recommended prior knowledge','clarodoc')}</td>
    <td> {{ #children }} <a id="{{ slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{workspace.slug}}/resources/{{slug}}">{{name}}</a>{{ /children }}</td>
  </tr>
  {{ /requirements}}
  <tr>
    <td>{trans('Last updated','clarodoc')}</td>
    <td>{{#resource.resourceNode.meta.updated}}{{formatDate}}{{/resource.resourceNode.meta.updated}}</td>
  </tr>
  </tbody>
</table>`,
        (props.showDescription) ? (props.descriptionTitle || `<h3>{trans('Learning outcomes','clarodoc')}</h3>`) : "",
        props.showDescription ? props.resource.resourceNode.meta.description : "",
        props.disclaimer ? props.disclaimer :
          `{{#resource.resourceNode.tags["disclaimer"] }}
          <h3>{trans('Disclaimer','clarodoc')}</h3>
          <p class="p1">{trans('This learning unit contains images that may not be accessible to some learners. This content is used to support learning. Whenever possible the information presented in the images is explained in the text.','clarodoc')}</p>
          {{/resource.resourceNode.tags["disclaimer"] }}`
    )
    return (
        <ResourceOverview
            contentText={
                <ContentHtmlComponent
                    store={{
                        resource:props.resource,
                        requirements:props.requirementResource
                            && props.requirementResource.children.length > 0 ?
                          props.requirementResource :
                          null
                    }}>
                    {overviewContent.join("")}
                </ContentHtmlComponent>
            }
            actions={[
            { // TODO : implement continue and restart
                type: CALLBACK_BUTTON,
                icon: 'fa fa-fw fa-play icon-with-text-right',
                label: trans('start'),
                disabled:isEmpty(props.widgets),
                disabledMessages: isEmpty(props.widgets) ? [trans('start_disabled_empty', {}, 'document')]:[],
                primary: true,
                callback: () => {
                    props.selectPage(1)
                }
            }
          ]}
          customActionsSection={
            <nav className="resource-overview-nav">
              <ul>{trans('Jump to', {}, 'clarodoc')}
              {props.widgets.filter(widget => widget.name && widget.name !== "")
                .map((widget, index) => {
                    return <li>
                      <CallbackButton
                        className="nav-link"
                        label={widget.name}
                        callback={() => {
                          props.selectPage(props.paginated ? index+1 : 1)
                        }}>{widget.name}</CallbackButton>
                    </li>
                  })}
              </ul>
            </nav>
          }>
        </ResourceOverview>

    )
}

DocumentOverview.propTypes = {
    description:T.string,
    overviewMessage:T.string,
    disclaimer:T.string,
    showDescription:T.boolean,
    currentContext: T.object,
    resource:T.object,
    requirementResource:T.object,
    path:T.string,
    widgets:T.arrayOf(T.object),
    paginated:T.bool,
    authorizeSummaryLinks:T.bool,
    selectPage:T.func.isRequired
}

export {
    DocumentOverview
}

/* code backup

{props.requirementResource &&
    <Widget
      key="requirements-widget"
      widget={{
        name:trans('requirements'),
        visible:true,
        display:{
            layout:[1],
            alignName:"left",
            color:"#333333",
            borderColor:null,
            backgroundType:"color",
            background:"#FFFFFF"
        },
        contents:[
            {
                type:'resource',
                source:'resource',
                parameters:{
                    resource:props.requirementResource,
                    showResourceHeader:false
                }
            }
        ]
      }}
      currentContext={props.currentContext}
    />
}
 */
