import React, {Fragment, useState} from 'react';

class SlugReloader extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            error: false,
            isLoaded: true
        };
    }

    render() {
        if (0 === this.props.title.length) {
            return <Fragment />;
        }
        let buttonClass = "btn btn-primary";
        let iconClass = "fa fa-refresh";
        if (this.state.error) {
            buttonClass = "btn btn-danger";
        }
        if (!this.state.isLoaded) {
            iconClass = "fa fa-refresh fa-spin";
        }
        return (
            <Fragment>
                <button className={buttonClass} type="button" onClick={() => this.handleClick()}>
                    <i className={iconClass}/>
                </button>
            </Fragment>
        );
    }

    handleClick() {
        this.setState({
            isLoaded: false
        });
        let url = this.props.resolver + '?content=' + this.props.content + '&title=' + document.getElementById(this.props.title).value;
        if (0 === this.props.content.length) {
            url = this.props.resolver + '?title=' + document.getElementById(this.props.title).value;
        }
        fetch(url)
            .then(res => res.json())
            .then(
                (result) => {
                    this.setState({
                        isLoaded: true,
                        error: false
                    });
                    document.getElementById(this.props.field).value = result.value;
                },
                () => {
                    this.setState({
                        isLoaded: true,
                        error: true
                    });
                }
            );
    }
}

export default SlugReloader;