import React, {Fragment} from 'react';

class ContentListConfiguration extends React.Component {
    constructor(props) {
        super(props);
        this.fields = [];
        this.formElement = {};
        this.state = {
            form: this.processForm(),
        };
    }

    processForm() {
        let temp = document.createElement('template');
        temp.innerHTML = '<div>' + this.props.form.trim() + '</div>';
        return temp.content.firstChild.firstChild;
    }

    render() {
        return (
            <Fragment>
                <div className="modal fade" id={'modal_' + this.props.id} tabIndex="-1">
                    <div className="modal-dialog modal-dialog-centered modal-lg">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title">{this.props.label}</h5>
                            </div>
                            <div className="modal-body" dangerouslySetInnerHTML={{ __html: this.state.form.innerHTML }} />
                            <div className="modal-footer">
                                <button type="button" className="btn btn-primary" data-bs-dismiss="modal">{this.props.save}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <a className="btn btn-primary" data-bs-toggle="modal" href={'#modal_' + this.props.id} role="button">{this.props.configure}</a>
            </Fragment>
        );
    }
}

export default ContentListConfiguration;