import React, { useEffect, useRef, useState } from "react";

import {
  Form,
  Card,
  CardBody,
  Col,
  Row,
  CardTitle,
  Container,
} from "reactstrap";

// Form Editor
import { CKEditor } from '@ckeditor/ckeditor5-react';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

//Import Breadcrumb
import Breadcrumbs from "../../components/Common/Breadcrumb";

const FormEditors = () => {
  const editorRef = useRef();
  const [editor, setEditor] = useState(false);
  const [data, setData] = useState(''); // Declare the "data" state variable
  
  useEffect(() => {
    editorRef.current = {
      CKEditor: CKEditor,
      ClassicEditor: ClassicEditor,
    };
    setEditor(true);
  }, []);
   
   //meta title
   document.title = "Form Editors | Skote React + Laravel 10 Admin And Dashboard Template"

  return (
    <React.Fragment>
      <div className="page-content">
        <Container fluid={true}>
          <Breadcrumbs title="Forms" breadcrumbItem="Form Editors" />
          <Row>
            <Col>
              <Card>
                <CardBody>
                  <CardTitle className="h4">CK Editor</CardTitle>
                  <p className="card-title-desc">
                    Super simple wysiwyg editor on Bootstrap
                  </p>

                  <Form method="post">
                    <CKEditor
                      editor={ClassicEditor}
                      data="<p>Hello from CKEditor 5!</p>"
                      onReady={editor => {
                        // You can store the "editor" and use when it is needed.
                        console.log('Editor is ready to use!', editor);
                      }}
                      onChange={(event, editor) => {
                        const data = editor.getData();
                      }}
                    />
                  </Form>
                </CardBody>
              </Card>
            </Col>
          </Row>
        </Container>
      </div>
    </React.Fragment>
  );
};

export default FormEditors;
