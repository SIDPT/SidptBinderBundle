import React,{Fragment, Component} from 'react'
import {PropTypes as T} from 'prop-types'

import isEmpty from 'lodash/isEmpty'

import {Translator, trans} from '#/main/app/intl/translation'

import {Routes} from '#/main/app/router'

import {ContentPlaceholder} from '#/main/app/content/components/placeholder'
import {Widget} from '#/main/core/widget/player/components/widget'
import {DocumentOverview} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc/components/overview'

import {Heading} from '#/main/core/layout/components/heading'
import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, LINK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'

import {LinkButton} from '#/main/app/buttons/link/components/button'

import {ProgressBar} from '#/main/app/content/components/progress-bar'



class DocumentPlayerMain extends Component {
  constructor(props) {
    super(props);
    this.state = {
      selectedPage:this.props.document.showOverview ? 0 : 1,
      widgetProgressionStart:-1,
      widgetProgression:0
    }

    this.selectPage = this.selectPage.bind(this)

    this.setWidgetProgression = this.setWidgetProgression.bind(this)

  }

  selectPage(index){
   this.setState({
      selectedPage:index
    })
    
  }

  setWidgetProgression(currentProgression, start=-1){
    this.setState({
      widgetProgression:currentProgression,
      widgetProgressionStart:start
    })
  }
  

  render(){
    // TODO document parameter to be stored
    //
    // Starting level depends on the display of resource title
    // should be the specified level + 1 if the resource header is shown
    const startingLevel = (this.props.resource.level + (this.props.resource.showHeader ? 1 : 0)) || 1
    let visibleWidgets = this.props.document.widgets.filter(
        widget => widget.visible === true
      ).map((widget,index)=>{
        let temp = Object.assign({}, widget);
        temp.name = trans(widget.name,{},'clarodoc');
        return temp;
      });

      return (
        <Fragment>
          {this.props.document.longTitle &&
            <Heading
              level={startingLevel || 2}
              className="page-title"
            >
              {this.props.document.longTitle}
            </Heading>
          }
          
          {this.props.document.widgetsPagination ?
              <Routes
                path={this.props.path}
                routes={[
                  {
                    path: '/',
                    exact:true,
                    disabled: !this.props.document.showOverview && visibleWidgets.length > 0,
                    render: (routeProps) => {

                      if(this.props.document.showOverview){
                        return (
                          <DocumentOverview
                            authorizeSummaryLinks={false}
                            resource={this.props.resource}
                            showDescription={this.props.document.showDescription}
                            descriptionTitle={this.props.document.descriptionTitle}
                            overviewMessage={this.props.document.overviewMessage}
                            disclaimer={this.props.document.disclaimer}
                            path={this.props.path}
                            widgets={visibleWidgets}
                            paginated={this.props.document.widgetsPagination}
                            startButton={{
                              icon:'fa fa-fw fa-play icon-with-text-right',
                              type:LINK_BUTTON,
                              label: trans('Start this unit', {}, 'clarodoc'),
                              disabled:isEmpty(visibleWidgets),
                              disabledMessages: isEmpty(visibleWidgets) ? [trans('start_disabled_empty', {}, 'clarodoc')]:[],
                              primary: true,
                              className:"btn btn-previous btn-block",
                              style:{marginRight:"5px"},
                              target:`${this.props.path}/${(visibleWidgets[0].slug || "section-1")}`
                            }}
                            selectPage={this.selectPage}
                            requirementResource={this.props.document.requiredResourceNodeTreeRoot}
                            currentContext={this.props.currentContext}
                          />
                        )
                      } else if(visibleWidgets.length == 0){
                        return (
                          <ContentPlaceholder
                            size="lg"
                            icon="fa fa-frown-o"
                            title={trans('no_section')}
                          />
                        )
                      } else { // redirect to first widget if overview is deactived and there are widgets defined
                        routeProps.history.push(`${this.props.path}/${(visibleWidgets[0].slug || "section-1")}`)
                        return null
                      }
                      
                    }
                  },{
                    path: '/:slug',
                    render: (routeProps) => {
                      // console.log(routeProps)
                      const page = visibleWidgets.findIndex((widget,index) => {
                        return routeProps.match.params.slug === (widget.slug || `section-${index+1}`)
                      })
                      
                      if (page >= 0) {
                        const progress = Math.floor(((page+1) / (visibleWidgets.length)) * 100) + Math.floor(this.state.widgetProgression / visibleWidgets.length)
                        return (<Fragment>
                          <div className="widgets-grid">
                            <nav className="widgets-nav">
                              <ul>
                                {this.props.document.showOverview && 
                                  <li>
                                    <LinkButton
                                    className="btn"
                                    style={{marginRight:"5px"}}
                                    exact={true}
                                    target={`${this.props.path}/`}
                                    title={trans('presentation', {}, 'clarodoc')}
                                    primary={false}
                                    active={false}
                                    onClick={()=>{
                                      this.setWidgetProgression(0)
                                    }}
                                  >
                                      {trans('presentation', {}, 'clarodoc')}
                                      <span>&nbsp;<span className="fa fa-angle-double-left icon-with-text-right" /></span>
                                  </LinkButton></li>
                                }
                                {visibleWidgets.map((widget,index) => {
                                  const beforePage = (page > index)
                                  const afterPage = (page < index)
                                  const isCurrent = page === index
                                  const slug = (widget.slug || `section-${index+1}`)
                                  return (<li>
                                    <LinkButton
                                      className={"btn"}
                                      style={{marginRight:"5px"}}
                                      target={`${this.props.path}/${slug}`}
                                      exact={true}
                                      disabled={isCurrent}
                                      primary={afterPage}
                                      title={(index+1) + ' - ' + (widget.name || (trans('section') + " " + (index+1)))}
                                      onClick={()=>{
                                        this.setWidgetProgression(0)
                                      }}
                                    >
                                      {afterPage && <span className='fa fa-angle-right icon-with-text-left'>&nbsp;</span>}
                                      {(index+1) + ' - ' + (widget.name || (trans('section') + " " + (index+1)))}
                                      {beforePage && <span>&nbsp;<span className="fa fa-angle-left icon-with-text-right" /></span>}
                                    </LinkButton>
                                  </li>)
                                })}
                              </ul>
                            </nav>
                            
                            <ProgressBar
                              className="progress-minimal"
                              value={progress}
                              size="m"
                              type="user"
                            />
                              {// TODO 
                               // - add previous and next button respectivaly on first and last page of the widget display
                               // - disable progression in subresources and update the upper progression bar
                              }
                              <Widget
                                key={page}
                                widget={visibleWidgets[page]}
                                currentContext={this.props.currentContext}
                                level={startingLevel + (this.props.document.longTitle ? 1 : 0)}
                                showProgression={false}
                                onProgressionChange={(newValue, startValue) => {
                                  this.setWidgetProgression(newValue, startValue)
                                }}
                                slug={
                                  routeProps.location.pathname.substr(routeProps.match.url.length).split('/').filter(v => v).join('/')
                                }
                                onSlugChange={(newSlug) => {
                                  console.log(newSlug)
                                  // Updating the currently displayed route based on the updated navigation slug within the resource
                                  //window.history.replaceState(null, "", path + newSlug)
                                }}
                                onStart={
                                  // (this.state.widgetProgressionStart == 0 && (page) > 0) ? (
                                  //   <LinkButton
                                  //       className={"btn component-container pull-left"}
                                  //       style={{marginRight:"5px"}}
                                  //       target={`${this.props.path}/${(visibleWidgets[page-1].slug || `section-${page+2}`)}`}
                                  //       primary={true}
                                  //       title={(visibleWidgets[page-1].name || (trans('section') + " " + (page)))}
                                  //       onClick={()=>{
                                  //         this.setWidgetProgression(0)
                                  //       }}
                                  //     >
                                  //       {(visibleWidgets[page-1].name || (trans('section') + " " + (page)))}
                                  //       <span>&nbsp;<span className="fa fa-angle-left icon-with-text-right" /></span>
                                  //     </LinkButton>
                                  //   ) : 
                                  false
                                }
                                onEnd={
                                  // (this.state.widgetProgression == 100 && (page+1) < visibleWidgets.length) ? (
                                  //   <LinkButton
                                  //       className={"btn component-container pull-right"}
                                  //       style={{marginRight:"5px"}}
                                  //       target={`${this.props.path}/${(visibleWidgets[page+1].slug || `section-${page+2}`)}`}
                                  //       primary={true}
                                  //       title={(visibleWidgets[page+1].name || (trans('section') + " " + (page+2)))}
                                  //       onClick={()=>{
                                  //         this.setWidgetProgression(0)
                                  //       }}
                                  //     >
                                  //       <span className='fa fa-angle-right icon-with-text-left'>&nbsp;</span>
                                  //       {(visibleWidgets[page+1].name || (trans('section') + " " + (page+2)))}
                                  //     </LinkButton>
                                  //   ) : 
                                  false
                                  
                                }

                              />
                              
                          </div>
                        </Fragment>)
                      }

                      routeProps.history.push(this.props.path+'/')

                      return null
                    }
                  }
                ]}
              />
            : // else display the document without routing, as one page of widget (possibly 2 with the overview page)
            <Fragment> 
              {this.props.document.showOverview && 0 === this.state.selectedPage &&
                <DocumentOverview
                    authorizeSummaryLinks={false}
                    resource={this.props.resource}
                    showDescription={this.props.document.showDescription}
                    descriptionTitle={this.props.document.descriptionTitle}
                    overviewMessage={this.props.document.overviewMessage}
                    disclaimer={this.props.document.disclaimer}
                    path={this.props.path}
                    widgets={visibleWidgets}
                    paginated={this.props.document.widgetsPagination}
                    startButton={{
                      className:"btn btn-block btn-primary",
                      type: CALLBACK_BUTTON,
                      icon:'fa fa-fw fa-play icon-with-text-right',
                      label: trans('Start this unit', {}, 'clarodoc'),
                      disabled:isEmpty(props.widgets),
                      disabledMessages: isEmpty(props.widgets) ? [trans('start_disabled_empty', {}, 'clarodoc')]:[],
                      primary: true,
                      callback: () => {
                        props.selectPage(1)
                      }
                    }}
                    requirementResource={this.props.document.requiredResourceNodeTreeRoot}
                    currentContext={this.props.currentContext}
                  />
              }
              {0 === visibleWidgets.length &&
                <ContentPlaceholder
                  size="lg"
                  icon="fa fa-frown-o"
                  title={trans('no_section')}
                />
              }

              {0 !== visibleWidgets.length && 0 < this.state.selectedPage &&
                <div className="widgets-grid">
                  {visibleWidgets.map((widget, index) =>
                    <Widget
                      key={index}
                      widget={widget}
                      currentContext={this.props.currentContext}
                      level={startingLevel + (this.props.document.longTitle ? 1 : 0)}
                    />
                  )}
                  { this.state.selectedPage > (this.props.document.showOverview ? 0 : 1) &&
                    <div className="widgets-nav-bottom">
                      <Button
                        className="btn btn-emphasis component-container pull-left"
                        type={CALLBACK_BUTTON}
                        callback={() => {
                          this.selectPage(this.state.selectedPage - 1);
                        }}
                        primary={true}
                      >
                        <span className="fa fa-angle-double-left icon-with-text-right" />
                        {trans('presentation', {}, 'clarodoc')}
                      </Button>
                    </div>
                  }
                </div>
              }
              </Fragment>
          }
        </Fragment>
        );
  }
}

/*

                      {
                        path: '/play/end',
                        disabled: !props.path.display.showEndPage,
                        render: () => (
                          <PlayerEnd
                            path={props.basePath}
                            pathId={props.path.id}
                            resourceId={props.resourceId}
                            currentUser={props.currentUser}
                            workspace={props.workspace}
                            steps={props.path.steps}
                            scoreTotal={props.path.score.total}
                            showScore={props.path.display.showScore}
                            endMessage={props.path.meta.endMessage}
                            attempt={props.attempt}
                            getAttempt={props.getAttempt}
                          />
                        )
                      }, 
 */

DocumentPlayerMain.propTypes = {
  document:T.object.isRequired,

  currentContext:T.object.isRequired
}

export {
  DocumentPlayerMain
}

/**
 *
 */
