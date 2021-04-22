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
    
    // Try to open the first document in the binder
    let displayedSlug = undefined;
    let content = undefined;
    if(this.props.binder.tabs.length > 0){
      let tabLookingQueue = this.props.binder.tabs.slice();
      let slug = '';
      do {
        let tab = tabLookingQueue.shift();
        let slug = tab.slug;
        if(tab.metadata.type === "document"){
          content = tab.content
          displayedSlug = tab.slug;
        } else if(tab.metadata.type === 'binder'){
          tabLookingQueue.push(...tab.content.binder.tabs);
        }
      } while(content === undefined && tabLookingQueue.length > 0);
    }
    
    this.state = {
      contentToDisplay:content,
      displayedSlug:displayedSlug
    }

    this.changeContentToDisplay = this.changeContentToDisplay.bind(this);
  }

  changeContentToDisplay(content, slug){
    this.setState({
      contentToDisplay:content,
      displayedSlug:slug
    })
  }

  render(){
    
    return (
      <Fragment>
        <BinderNavigator 
            binder={this.props.binder}
            selectedSlug={this.state.displayedSlug}
            onContentSelected={this.changeContentToDisplay}
        />
        
        {this.state.contentToDisplay === undefined &&
          <ContentPlaceholder
            size="lg"
            icon="fa fa-frown-o"
            title={trans('no_section')}
          />
        }
        {this.state.contentToDisplay && 
          <DocumentPlayerMain 
              document={this.state.contentToDisplay.clarodoc}
              currentContext={this.props.currentContext} />
        }
      </Fragment>
    );
  }
}

BinderPlayerMain.propTypes = {
  binder:T.object.isRequired,
  selectedTabPath:T.string,
  currentContext:T.object.isRequired
}

export {
  BinderPlayerMain
}
