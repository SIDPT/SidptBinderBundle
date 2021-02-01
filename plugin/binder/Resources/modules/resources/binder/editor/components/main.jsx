import React, {Fragment, Component} from 'react'
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
import classes from 'classnames'

import {PropTypes as T} from 'prop-types'
import cloneDeep from 'lodash/cloneDeep'
import get from 'lodash/get'
import isEmpty from 'lodash/isEmpty'


import {LINK_BUTTON} from '#/main/app/buttons'
import {FormData} from '#/main/app/content/form/containers/data'

import {makeId} from '#/main/core/scaffolding/id'
import {trans} from '#/main/app/intl/translation'
import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'
import {ContentPlaceholder} from '#/main/app/content/components/placeholder'

import {TabsList} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/components/tabslist'


import {selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/store/selectors'

class BinderEditorMain extends Component {
  constructor(props) {
    super(props)

    this.state = {
      movingContentId: null,
      currentTabIndex: 0
    }
  }

  startMovingContent(contentId) {
    this.setState({movingContentId: contentId})
  }

  stopMovingContent() {
    this.setState({movingContentId: null})
  }

  getFormSection(selectedTab){
    return {
        title: `tabs[${selectedTab}].title`,
        primary: true,
        fields: [
          {
              name: `tabs[${selectedTab}].title`,
              label: trans('title'),
              type: 'string',
              required: false
          }, {
            name: `tabs[${selectedTab}].position`,
            label: trans('position'),
            type: 'number',
            required: true
          }, {
            name: `tabs[${selectedTab}].resourceNode`,
            label: trans('resource'),
            type: 'resource',
            validating:(data)=>{console.log(data)},
            required: false
          },
          {
              name: `tabs[${selectedTab}].metadata.backgroundColor`,
              label: trans('background_color'),
              type: 'color',
              required: false
          },
          {
              name: `tabs[${selectedTab}].metadata.borderColor`,
              label: trans('border_color'),
              type: 'color',
              required: false
          },
          {
              name: `tabs[${selectedTab}].metadata.textColor`,
              label: trans('text_color'),
              type: 'color',
              required: false
          },
          {
              name: `tabs[${selectedTab}].metadata.icon`,
              label: trans('icon'),
              type: 'icon',
              required: false
          },
          {
            name: `tabs[${selectedTab}].restrictions.hidden`,
            type: 'boolean',
            label: trans('restrict_hidden')
          }, {
            name: `tabs[${selectedTab}].restrictByRole`,
            type: 'boolean',
            label: trans('restrictions_by_roles', {}, 'widget'),
            calculated: (tab) => tab.restrictByRole || !isEmpty(get(tab, 'metadata.roles')),
            onChange: (checked) => {
              if (!checked) {
                this.props.update(`tabs[${selectedTab}].metadata.roles`, [])
              }
            },
            linked: [
              {
                name: `tabs[${selectedTab}].metadata.roles`,
                label: trans('roles'),
                displayed: (tab) => tab.restrictByRole || !isEmpty(get(tab, 'metadata.roles')),
                type: 'roles',
                required: true,
                options: {
                  picker: this.props.currentContext.type === 'workspace' ? {
                    url: ['apiv2_workspace_list_roles', {id: get(this.props.currentContext, 'data.id')}],
                    filters: []
                  } : undefined
                }
              }
            ]
          }
        ]
      };
  }

  render() {

    const tabs = this.props.binder.tabs.slice(0);
    const collapsed = {
      visibility:'hidden',
      display:'none'
    }
    const visible = {
      visibility:'visible',
    }
    const forms = tabs.length === 0 ? [] : tabs.map(
      (tab,index) => 
        <div key={tab.id} style={
          index === this.state.currentTabIndex ? visible : collapsed
        }>
          <FormData
              level={2}
              name={`${selectors.FORM_NAME}`}
              buttons={true}
              target={(binder) => ['sidpt_binder_update', {id: binder.id}]}
              cancel={{
                  type: LINK_BUTTON,
                  target: this.props.path,
                  exact: true
                }}
              sections={[ this.getFormSection(index) ]}
            >
            <Button className="delete-tab"
                  type={CALLBACK_BUTTON}
                  icon="fa fa-fw fa-trash"
                  label={trans('delete')}
                  callback={() => {
                    tabs.splice(this.state.currentTabIndex,1);
                    this.props.update('tabs',tabs);
                    this.setState({currentTabIndex:this.state.currentTabIndex-1})
                  }}
                >{trans('delete')}</Button>
            </FormData>
          </div>
      );

    return (
      <Fragment>
        <TabsList 
          prefix={this.props.path}
          tabs={tabs}
          onClick={(index) => {
            this.setState({currentTabIndex:index})
          }}
          create={()=>{
            tabs.push({
              id:null,
              position:tabs.length,
              metadata:{
                type:'undefined',
                roles:[]
              },
              content:undefined

            });
            this.props.update('tabs',tabs);
            this.setState({currentTabIndex:tabs.length - 1})
          }}
        />
        


        {0 === tabs.length &&
          <ContentPlaceholder title={trans('no_section')}/> 
        }    
        
        {0 !== tabs.length && forms }

        
        
        
      </Fragment> )
  
  }
}
    


BinderEditorMain.propTypes = {
  path: T.string.isRequired,
  binder: T.object.isRequired,
  currentContext: T.object.isRequired,
  update: T.func.isRequired,
  updateTab: T.func.isRequired,
  save: T.func.isRequired
}

export {
  BinderEditorMain
}


/**
 * 
          
 * <div className={classes('form-toolbar')}>
        <Button
          className="nav-add-tab"
          type={CALLBACK_BUTTON}
          icon="fa fa-fw fa-plus"
          label={trans('add_tab')}
          tooltip="top"
          callback={() => {
            tabs.push({
              id:null,
              metadata:{
                type:'undefined',
                roles:[]
              },
              content:undefined

            });
            this.props.update('tabs',tabs);
          }}
        />
        <Button
            icon="fa fa-fw fa-save"
            label={trans('save', {}, 'actions')}
            type={CALLBACK_BUTTON}
            tooltip="top"
            callback={() => {
              this.props.save(this.props.binder.id);
            }}
            primary={true}
          />
        <Button
            icon="fa fa-fw fa-sign-out-alt"
            label={trans('exit', {}, 'actions')}
            type={LINK_BUTTON}
            tooltip="bottom"
            target={this.props.path}
            exact={true}
            primary={true}
            htmlType="submit"
          />
      </div>
 */