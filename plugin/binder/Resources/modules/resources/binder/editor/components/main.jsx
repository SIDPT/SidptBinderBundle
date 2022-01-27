import React, {Fragment, Component} from 'react'
import classes from 'classnames'

import {PropTypes as T} from 'prop-types'
import cloneDeep from 'lodash/cloneDeep'
import get from 'lodash/get'
import isEmpty from 'lodash/isEmpty'


import {LINK_BUTTON} from '#/main/app/buttons'
import {FormData} from '#/main/app/content/form/containers/data'

import {makeId} from '#/main/core/scaffolding/id'
import {Translator, trans} from '#/main/app/intl/translation'
import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'
import {ContentPlaceholder} from '#/main/app/content/components/placeholder'

import {TabsList} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/components/tabslist'


import {selectors} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/store/selectors'

import {Tab} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/prop-types'

const authorizedTypes = [
  'sidpt_binder',
  'sidpt_document'
]

class BinderEditorMain extends Component {
  constructor(props) {
    super(props)

    this.state = {
      movingContentId: null,
      currentTabIndex: 0
    }
  }

  changeTab(tabId){
    this.setState({currentTabIndex: tabId})
  }

  getFormSection(tabs, selectedTab){
    return [
      {
        title: `binder.tabs[${selectedTab}].title`,
        primary: true,
        fields: [
          {
              name: `binder.tabs[${selectedTab}].title`,
              label: trans('title'),
              type: 'string',
              required: false
          }, {
            name: `binder.tabs[${selectedTab}].resourceNode`,
            label: trans('resource'),
            type: 'resource',
            required: false,
            options: {
              picker: {
                current: tabs[selectedTab].resourceNode && tabs[selectedTab].resourceNode.parent ? tabs[selectedTab].resourceNode.parent : null,
                root: null,
                filters: [{property: 'resourceType', value: ['directory'].concat(authorizedTypes), locked: true}]
              }
            },
            onChange: (resource) => {
              if (-1 === authorizedTypes.indexOf(get(resource, 'meta.type'))) {
                this.props.update(`binder.tabs[${selectedTab}].resourceNode`, null)
              }
            }
          }
        ]
      }, {
        title:trans('display'),
        fields:[
          {
            name: `binder.tabs[${selectedTab}].display.position`,
            label: trans('position'),
            type: 'number',
            onChange:(value)=>{
              // recompute positions
              
            },
            required: true
          }, 
          {
              name: `binder.tabs[${selectedTab}].display.backgroundColor`,
              label: trans('background_color'),
              type: 'color',
              required: false
          },
          {
              name: `binder.tabs[${selectedTab}].display.borderColor`,
              label: trans('border_color'),
              type: 'color',
              required: false
          },
          {
              name: `binder.tabs[${selectedTab}].display.textColor`,
              label: trans('text_color'),
              type: 'color',
              required: false
          },
          {
              name: `binder.tabs[${selectedTab}].display.icon`,
              label: trans('icon'),
              type: 'icon',
              required: false
          },{
            name: `binder.tabs[${selectedTab}].display.visible`,
            type: 'boolean',
            label: trans('visible')
          }
        ]
      }, {
        title:trans('restrictions'),
        fields:[ 
          {
            name: `binder.tabs[${selectedTab}].restrictByRole`,
            type: 'boolean',
            label: trans('restrictions_by_roles', {}, 'widget'),
            calculated: (data) => (
                data.binder.tabs[selectedTab].restrictByRole || 
                !isEmpty(get(data.binder.tabs[selectedTab], 'metadata.roles'))
              ),
            onChange: (checked) => {
              if (!checked) {
                this.props.update(`binder.tabs[${selectedTab}].metadata.roles`, [])
              }
            },
            linked: [
              {
                name: `binder.tabs[${selectedTab}].metadata.roles`,
                label: trans('roles'),
                displayed: (data) => (
                    data.binder.tabs[selectedTab].restrictByRole || 
                    !isEmpty(get(data.binder.tabs[selectedTab], 'metadata.roles'))
                ),
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
      }
    ];
  }

  render() {

    const tabs = this.props.data.binder.tabs.slice(0);
    const collapsed = {
      visibility:'hidden',
      display:'none'
    }
    const visible = {
      visibility:'visible',
    }
    // const forms = tabs.length === 0 ? [] : tabs.map(
    //   (tab,index) => 
    //     <div key={tab.id} style={
    //       index === this.state.currentTabIndex ? visible : collapsed
    //     }>
    //       <FormData
    //           level={2}
    //           name={selectors.FORM_NAME}
    //           buttons={true}
    //           target={(data) => ['sidpt_binder_update', {id: data.binder.id}]}
    //           cancel={{
    //               type: LINK_BUTTON,
    //               target: this.props.path,
    //               exact: true
    //             }}
    //           sections={this.getFormSection(tabs, index)}
    //         >
    //         <Button className="delete-tab"
    //               type={CALLBACK_BUTTON}
    //               icon="fa fa-fw fa-trash"
    //               label={trans('delete')}
    //               callback={() => {
    //                 tabs.splice(this.state.currentTabIndex,1);
    //                 this.props.update('binder.tabs',tabs);
    //                 this.setState({currentTabIndex:this.state.currentTabIndex-1})
    //               }}
    //             >{trans('delete')}</Button>
    //         </FormData>
    //       </div>
    //   );
    const currentTab = tabs.length > 0 ? tabs[this.state.currentTabIndex] : undefined;
    return (
      <Fragment>
        <TabsList 
          prefix={this.props.path}
          tabs={tabs.sort(
            (tab1, tab2) => {
              return tab1.metadata.position - tab2.metadata.position;
            })}
          onClick={(index) => {
            this.changeTab(index)
          }}
          create={()=>{
            tabs.push(cloneDeep(Tab.defaultProps));
            this.props.update('binder.tabs',tabs);
            this.changeTab(tabs.length - 1);
          }}
        />
        


        {0 === tabs.length &&
          <ContentPlaceholder title={trans('no_section')}/> 
        }    
        
        {/*{0 !== tabs.length && forms[this.state.currentTabIndex] }*/}

        {0 !== tabs.length && 
          <FormData
              level={2}
              name={selectors.FORM_NAME}
              dataPart={`binder.tabs[${this.state.currentTabIndex}]`}
              buttons={true}
              target={(data) => {
                  return ['sidpt_binder_update', {id: this.props.data.binder.id}]
              }}
              cancel={{
                  type: LINK_BUTTON,
                  target: this.props.path,
                  exact: true
                }}
              sections={[
                {
                  title: `title`,
                  primary: true,
                  fields: [
                    {
                        name: `title`,
                        label: trans('title'),
                        type: 'string',
                        required: false
                    }, {
                      name: `resourceNode`,
                      label: trans('resource'),
                      type: 'resource',
                      required: false,
                      options: {
                        picker: {
                          current: currentTab.resourceNode && currentTab.resourceNode.parent ? currentTab.resourceNode.parent : null,
                          root: null,
                          filters: [{property: 'resourceType', value: ['directory'].concat(authorizedTypes), locked: true}]
                        }
                      },
                      onChange: (resource) => {
                        if (-1 === authorizedTypes.indexOf(get(resource, 'meta.type'))) {
                          this.props.update(`resourceNode`, null)
                        }
                      }
                    }
                  ]
                }, {
                  title:trans('display'),
                  fields:[
                    {
                      name: `display.position`,
                      label: trans('position'),
                      type: 'number',
                      onChange:(value)=>{
                        // recompute positions
                        
                      },
                      required: true
                    }, 
                    {
                        name: `display.backgroundColor`,
                        label: trans('background_color'),
                        type: 'color',
                        required: false
                    },
                    {
                        name: `display.borderColor`,
                        label: trans('border_color'),
                        type: 'color',
                        required: false
                    },
                    {
                        name: `display.textColor`,
                        label: trans('text_color'),
                        type: 'color',
                        required: false
                    },
                    {
                        name: `display.icon`,
                        label: trans('icon'),
                        type: 'icon',
                        required: false
                    },{
                      name: `display.visible`,
                      type: 'boolean',
                      label: trans('visible')
                    }
                  ]
                }, {
                  title:trans('restrictions'),
                  fields:[ 
                    {
                      name: `restrictByRole`,
                      type: 'boolean',
                      label: trans('restrictions_by_roles', {}, 'widget'),
                      calculated: (data) => (
                          data.restrictByRole || 
                          !isEmpty(get(data, 'metadata.roles'))
                        ),
                      onChange: (checked) => {
                        if (!checked) {
                          this.props.update(`metadata.roles`, [])
                        }
                      },
                      linked: [
                        {
                          name: `metadata.roles`,
                          label: trans('roles'),
                          displayed: (data) => {
                            return (
                              data.restrictByRole || 
                              !isEmpty(get(data, 'metadata.roles'))
                          )},
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
                }
              ]}
            >
            <Button className="delete-tab"
                  type={CALLBACK_BUTTON}
                  icon="fa fa-fw fa-trash"
                  label={trans('delete')}
                  callback={() => {
                    tabs.splice(this.state.currentTabIndex,1);
                    this.props.update('binder.tabs',tabs);
                    this.setState({currentTabIndex:this.state.currentTabIndex-1})
                  }}
                />
            </FormData> }

        
        
        
      </Fragment> )
  
  }
}
    


BinderEditorMain.propTypes = {
  path: T.string.isRequired,
  data: T.object.isRequired,
  currentContext: T.object.isRequired,
  update: T.func.isRequired,
  updateTab: T.func.isRequired,
  save: T.func.isRequired
}

export {
  BinderEditorMain
}
