import React, {Fragment} from 'react'
import {object, PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import isEmpty from 'lodash/isEmpty'

import {trans} from '#/main/app/intl/translation'
import {scrollTo} from '#/main/app/dom/scroll'
import {CALLBACK_BUTTON, CallbackButton} from '#/main/app/buttons'
import {Button} from '#/main/app/action/components/button'
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
    const overviewMessage = props.overviewMessage ? props.overviewMessage :
    `<table
        class="table-hover lu-table"
        style="font-weight:bold;"
        border="1"
        cellspacing="5px"
        cellpadding="20px">
      <tbody>
      <tr>
        <th scope="row">{trans('Learning unit','clarodoc')}</th>
        <td>
          <ul>
            <li><b>{trans('Course','clarodoc')}:</b> <a id="{{ resource.resourceNode.path[-3].slug }}" class="default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-3].slug}}">{{ resource.resourceNode.path[-3].name }}</a></li>
            <li><b>{trans('Module','clarodoc')}:</b> <a id="{{ resource.resourceNode.path[-2].slug }}" class="default" href="#/desktop/workspaces/open/{{resource.resourceNode.workspace.slug}}/resources/{{resource.resourceNode.path[-2].slug}}">{{ resource.resourceNode.path[-2].name }}</a></li>
            <li><b>{trans('Learning unit','clarodoc')}:</b> {{ resource.resourceNode.name }}</li>
          </ul>
        </td>
      </tr>
      <tr>
        <th scope="row">{trans('Professional profiles','clarodoc')}</th>
        <td>{{#resource.resourceNode.tags["professional-profile"]}}{{childrenNames}}{{/resource.resourceNode.tags["professional-profile"]}}</td>
      </tr>
      <tr>
        <th scope="row">{trans('Learning objects','clarodoc')}</th>
        <td>{{#resource.resourceNode.tags["included-resource-type"]}}{{childrenNames}}{{/resource.resourceNode.tags["included-resource-type"]}}</td>
      </tr>
      <tr>
        <th scope="row">{trans('Approximate duration','clarodoc')}</th>
        <td>{{#resource.resourceNode.tags["time-frame"]}}{{childrenNames}}{{/resource.resourceNode.tags["time-frame"]}}</td>
      </tr>
      {{ #requirements}}
      <tr>
        <th scope="row">{trans('Recommended prior knowledge','clarodoc')}</th>
        <td> {{ #children }} <a id="{{ slug }}" class="list-primary-action default" href="#/desktop/workspaces/open/{{workspace.slug}}/resources/{{slug}}">{{name}}</a>{{ /children }}</td>
      </tr>
      {{ /requirements}}
      <tr>
        <th scope="row">{trans('Last updated','clarodoc')}</th>
        <td>{{#resource.resourceNode.meta.updated}}{{formatDate}}{{/resource.resourceNode.meta.updated}}</td>
      </tr>
      </tbody>
    </table>`
    


    return (
      <section className='resource-section resource-overview'>
        <div className='row'>
          {props.showDescription && <div class='user-column col-md-12'>
            {props.descriptionTitle || <h3>{trans('Learning outcomes',{},'clarodoc')}</h3>}
            <ContentHtmlComponent
                store={{
                    resource:props.resource,
                    requirements:props.requirementResource
                        && props.requirementResource.children.length > 0 ?
                      props.requirementResource :
                      null
                }}>
                {props.resource.resourceNode.meta.description}
            </ContentHtmlComponent>
            
          </div>}
          <div class='resource-column col-md-8'>
            {props.startButton && <Button {...props.startButton}/>}
          </div>
          <div class='resource-column col-md-12'>
            {props.disclaimer ? 
              props.disclaimer : (
                props.resource.resourceNode.tags && props.resource.resourceNode.tags.disclaimer && <Fragment>
                  <h3>{trans('Disclaimer',{},'clarodoc')}</h3>
                  <p class="p1">{trans('This learning unit contains images that may not be accessible to some learners. This content is used to support learning. Whenever possible the information presented in the images is explained in the text.',{}, 'clarodoc')}</p>
                </Fragment>
                )
            }
            <ContentHtmlComponent
                store={{
                    resource:props.resource,
                    requirements:props.requirementResource
                        && props.requirementResource.children.length > 0 ?
                      props.requirementResource :
                      null
                }}>
                {overviewMessage}
            </ContentHtmlComponent>
          </div>
        </div>

      </section>
        

    )
    
}

/* 
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

*/

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
