import React,{Fragment, Component} from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {ContentPlaceholder} from '#/main/app/content/components/placeholder'

import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'


import {TabsList} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/components/tabslist'

import {BinderNavigator} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/player/components/nav'

import {DocumentPlayerMain} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/player/components/main'

class BinderPlayerMain extends Component {
  constructor(props){
    super(props);
    
  }
  
  render(){
    return (
      <Fragment>
        <BinderNavigator 
            binder={this.props.binder}
            displayedTabs={this.props.displayedTabs}
            displayedContentSlug={this.props.displayedDocument.slug}
            onTabSelected={this.props.getBinderTabContent}
            onBinderSelected={this.props.resetBinder}
        />
        
        {this.props.displayedDocument === undefined &&
          <ContentPlaceholder
            size="lg"
            icon="fa fa-frown-o"
            title={trans('no_section')}
          />
        }
        {this.props.displayedDocument && 
          <DocumentPlayerMain 
              document={this.props.displayedDocument.clarodoc}
              currentContext={this.props.currentContext} />
        }
      </Fragment>
    );
  }
}

BinderPlayerMain.propTypes = {
  binder:T.object.isRequired,
  displayedDocument:T.object,
  displayedTabs:T.arrayOf(T.object),
  getBinderTabContent:T.func,
  resetBinder:T.func,
  selectedTabPath:T.string,
  currentContext:T.object.isRequired
}

export {
  BinderPlayerMain
}
