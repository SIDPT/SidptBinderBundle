import React,{Fragment, Component} from 'react'
import {PropTypes as T} from 'prop-types'

import {Translator, trans} from '#/main/app/intl/translation'

import {ContentPlaceholder} from '#/main/app/content/components/placeholder'
import {Widget} from '#/main/core/widget/player/components/widget'
import {DocumentOverview} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/components/overview'

import {CallbackButton} from '#/main/app/buttons/callback/components/button'
import {Button} from '#/main/app/action/components/button'
import {CALLBACK_BUTTON, MODAL_BUTTON} from '#/main/app/buttons'

class DocumentPlayerMain extends Component {
  constructor(props) {
    super(props);

    const translations = this.props.document.translations;
    if(translations.length > 0){
      for(const field of translations){
        for(const locale in field.locales){
          if(field.locales[locale].length > 0){
            Translator.add(
              field.path,
              field.locales[locale],
              `${this.props.document.id}`,
              locale);
          }
        }
      }
    }

    this.state = {
      selectedPage:this.props.document.showOverview ? 0 : 1
    }

    this.selectPage = this.selectPage.bind(this)

  }

  selectPage(index, widget=null){
    this.setState({
      selectedPage:index
    })
  }

  render(){
    // TODO document parameter to be stored
    

    let visibleWidgets = this.props.document.widgets.filter(
        widget => widget.visible === true
      ).map((widget,index)=>{
        let temp = Object.assign({}, widget);
        let existingTranslation = trans(`widgets[${index}].name`,{},`${this.props.document.id}`);
        if(existingTranslation && 
              existingTranslation.length > 0 && 
              existingTranslation !== `widgets[${index}].name`){
          temp.name = existingTranslation;
        }
        return temp;
      });

      return (
        <Fragment>
          <header className={this.props.document.centerTitle ? "text-center" : ''}> 
            <h1 className="page-title">{this.props.document.longTitle}</h1>
          </header>
          {this.props.document.showOverview && 0 === this.state.selectedPage && 
              <DocumentOverview
                authorizeSummaryLinks={false}
                resource={this.props.resource}
                path={this.props.path}
                widgets={visibleWidgets}
                paginated={this.props.document.widgetsPagination}
                selectPage={this.selectPage}
                requirementResource={this.props.document.requiredResourceNodeTreeRoot}
                currentContext={this.props.currentContext}
              />
            }

          {this.props.document.widgetsPagination ? 
            <Fragment>
              {0 < this.state.selectedPage && 
                <div className="widgets-grid">
                  <Widget
                      key={this.state.selectedPage}
                      widget={visibleWidgets[this.state.selectedPage-1]}
                      currentContext={this.props.currentContext}
                    />
                  <div class="widgets-nav-bottom">
                    <Button
                      className="btn btn-emphasis component-container pull-left"
                      type={CALLBACK_BUTTON}
                      label={trans('back')}
                      callback={() => {
                        this.selectPage(this.state.selectedPage - 1);
                      }}
                      primary={true}
                    />
                    {visibleWidgets.length > this.state.selectedPage &&
                        <Button
                          className="btn btn-emphasis component-container pull-right"
                          type={CALLBACK_BUTTON}
                          label={trans('next')}
                          callback={() => {
                            this.selectPage(this.state.selectedPage + 1);
                          }}
                          primary={true}
                        />
                    }
                  </div>
                </div>
              }
            </Fragment>
            :
            <Fragment>
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
                    />
                  )}
                  { this.state.selectedPage > (this.props.document.showOverview ? 0 : 1) &&
                    <div class="widgets-nav-bottom">
                      <Button
                        className="btn btn-emphasis component-container pull-left"
                        type={CALLBACK_BUTTON}
                        label={trans('back')}
                        callback={() => {
                          this.selectPage(this.state.selectedPage - 1);
                        }}
                        primary={true}
                      />
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