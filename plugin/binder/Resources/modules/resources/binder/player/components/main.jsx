import React,{Fragment, Component} from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {ContentPlaceholder} from '#/main/app/content/components/placeholder'

import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'


import {TabsList} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/components/tabslist'

import {DocumentPlayerMain} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/player/components/main'

class BinderPlayerMain extends Component {
  constructor(props){
    super(props);

    this.state = {
      currentTabIndex:0
    }
  }

  render(){ 

    let visibleTabs = this.props.binder.tabs.filter(
        tab => tab.metadata.visible === true
    );

    const contents = visibleTabs.length === 0 ? [] :  visibleTabs.map(
      (tab,index) => {
        if (tab.metadata.type === 'document'){
          return (
              <DocumentPlayerMain 
                clarodoc={tab.content}
                currentContext={this.props.currentContext} />);
        } else if(tab.metadata.type === 'binder'){
          return (
              <BinderPlayerMain 
                binder={tab.content} 
                currentContext={this.props.currentContext} />);

        } else return (
            <ContentPlaceholder
                size="lg"
                icon="fa fa-frown-o"
                title={trans('no_section')} />);
      });


    return (
      <Fragment>
        {0 === visibleTabs.length &&
          <ContentPlaceholder
            size="lg"
            icon="fa fa-frown-o"
            title={trans('no_section')}
          />
        }
        {0 !== visibleTabs.length && 
          <TabsList 
              prefix={this.props.path}
              tabs={visibleTabs}
              onClick={(index) => {
                this.setState({currentTabIndex:index})
              }}
          />
        }

        {0 !== visibleTabs.length && contents[this.state.currentTabIndex]}
        

      </Fragment>
    );
  }
}

BinderPlayerMain.propTypes = {
  binder:T.object.isRequired,
  currentContext:T.object.isRequired
}

export {
  BinderPlayerMain
}
