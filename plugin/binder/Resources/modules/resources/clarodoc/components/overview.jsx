import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import isEmpty from 'lodash/isEmpty'

import {trans} from '#/main/app/intl/translation'
import {scrollTo} from '#/main/app/dom/scroll'
import {CALLBACK_BUTTON} from '#/main/app/buttons'
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
    
    return (
        <ResourceOverview
            contentText={
                <Fragment>
                    <ContentHtmlComponent
                        store={{
                            resource:props.resource
                        }}>
                        {props.resource.resourceNode.meta.description}
                    </ContentHtmlComponent>
                    
                </Fragment>
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
            ]}>
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
            <section className="resource-section resource-overview">
                <h3 className="h2">{trans('summary')}</h3>
                <ContentSummary
                  className="component-container"
                  links={props.widgets.map((widget, index) => {
                    return {
                      type: CALLBACK_BUTTON,
                      label: widget.name,
                      disabled:!props.authorizeSummaryLinks,
                      callback: () => {
                        props.selectPage(props.paginated ? index+1 : 1)
                      }
                    }
                  })}
                />
          </section>
        </ResourceOverview>
    )
}

DocumentOverview.propTypes = {
    description:T.string,
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