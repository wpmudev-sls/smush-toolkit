import React from 'react';
import Button from '@material-ui/core/Button';
import Box from '@material-ui/core/Box';
import LinearProgress from '@material-ui/core/LinearProgress';
import axios from 'axios';
import Alert from '@material-ui/lab/Alert';
import TextField from '@material-ui/core/TextField';


import Table from '@material-ui/core/Table';
import TableBody from '@material-ui/core/TableBody';
import TableCell from '@material-ui/core/TableCell';
import TableContainer from '@material-ui/core/TableContainer';
import TableHead from '@material-ui/core/TableHead';
import TableRow from '@material-ui/core/TableRow';

import Grid from '@material-ui/core/Grid';
import Paper from '@material-ui/core/Paper';

class ListImages extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			loading: false,
			completed: false,
			images: Array(),
			repairWidthInput: '2560',
			repairHeightInput: '2560',
			repairImageLimitInput: '50'
		};

		this.repair_images = this.repair_images.bind(this);
		this.replace_original_images = this.replace_original_images.bind(this);
	}

	replace_original_images() {
		const Api = axios.create({
			baseURL: regenerate_image.data.rest_url,
			headers: {
				'content-type': 'application/json',
				'X-WP-Nonce': regenerate_image.data.nonce
			}
		}),
		self = this,
		data = {
			action : 'replace-original-images',
			minWidth: this.state.repairWidthInput,
			minHeight: this.state.repairHeightInput,
			limit: this.state.repairImageLimitInput,
		};

		self.setState({ completed: false });

		Api.post( regenerate_image.data.rest_replace_origs_namespace, data ).then( function (response) {

			if ( response.data.success ) {

				if ( ! response.data.completed ) {
					self.setState({images: [...self.state.images, response.data.message ]});
					self.setState({ loading: true });
					self.replace_original_images();
				} else {
					self.setState({ loading: false });
					self.setState({ completed: true });
				}

			} else {
				self.setState({ loading: false });
			}
		});
	}

	repair_images() {
		const Api = axios.create({
			baseURL: regenerate_image.data.rest_url,
			headers: {
				'content-type': 'application/json',
				'X-WP-Nonce': regenerate_image.data.nonce
			}
		}),
		self = this,
		data = {
			action : 'regenerate-image-repair'
		};

		self.setState({ completed: false });
		Api.post( regenerate_image.data.rest_namespace, data ).then( function (response) {

			if ( response.data.success ) {

				if ( ! response.data.completed ) {
					self.setState({images: [...self.state.images, response.data.message ]});
					self.setState({ loading: true });
					self.repair_images();
				} else {
					self.setState({ loading: false });
					self.setState({ completed: true });
				}

			} else {
				self.setState({ loading: false });
			}
		});
	  }

	  list_images() {

		return (
			<TableContainer component={Paper}>
      			<Table aria-label="simple table">
					<TableBody>
						{this.state.images.map((row) => (
							<TableRow>
								<TableCell align="left">{this.display_row_html( row )}</TableCell>
							</TableRow>
						))}
					</TableBody>
		  		</Table>
		  </TableContainer>
		);
	}

	display_row_html( raw_html ) {
		return <div>
					{ <div dangerouslySetInnerHTML={{ __html: raw_html }} /> }
				</div>;
	}

	repairWidthInputChange = (e) => this.setState({ 
		repairWidthInput: e.target.value 
	})

	repairHeightInputChange = (e) => this.setState({ 
		repairHeightInput: e.target.value 
	})

	repairImageLimitInputChange = (e) => this.setState({ 
		repairImageLimitInput: e.target.value 
	})

	render() {
		var button = '',
		progressbar = '',
		notification = '';

		if ( ! this.state.loading ) {
			button = <Grid container spacing={3}>

						<Grid item xs={6}>
							<Paper elevation={1}>
								<Box p={1} m={1}>
									<Alert severity="info">
										Repairs some specific image meta <br />
										For further details check task SLS-2584.
									</Alert>

								</Box>
								<Box p={1} m={1}>
									<Button variant="contained" color="primary" onClick={ this.repair_images }>
										Repair images
									</Button>
								</Box>
							</Paper>
						</Grid>


						<Grid item xs={6}>
							<Paper elevation={1}>
								<Box p={1} m={1}>
									<Alert severity="info">
										Replaces the original images with the scaled ones. This way site still has original images but much smaller ones instead of deleting them.<br />
										Usefull for sites with huge original images.
									</Alert>

								</Box>
								<Grid container xs={12}>
									<Grid item xs={4}>
										<Box p={1}>
										<TextField
											id="min-width-limit"
											label="Min Width"
											helperText="Replace only images that have width larger than this limit"
											type="number"
											InputLabelProps={{
											shrink: true,
											}}
											fullWidth
											onChange={this.repairWidthInputChange}
											value={this.state.repairWidthInput}
										/>
										</Box>
									</Grid>

									<Grid item xs={4}>
										<Box p={1}>
										<TextField
											id="min-height-limit"
											label="Min Height"
											helperText="Replace only images that have heigth larger than this limit"
											type="number"
											InputLabelProps={{
											shrink: true,
											}}
											fullWidth
											onChange={this.repairHeightInputChange}
											value={this.state.repairHeightInput}
										/>
										</Box>
									</Grid>

									<Grid item xs={4}>
										<Box p={1}>
											<TextField
												id="images-limit"
												label="Images per request"
												helperText="Number of images to replace on each xhr request"
												type="number"
												InputLabelProps={{
												shrink: true,
												}}
												fullWidth
												onChange={this.repairImageLimitInputChange}
												value={this.state.repairImageLimitInput}
											/>
										</Box>
									</Grid>
									
									
									
								</Grid>
								<Box p={1} m={1}>
									<Button variant="contained" color="primary" onClick={ this.replace_original_images }>
										Replace originals
									</Button>
								</Box>

								<Alert severity="warning">This will replace original images which might cause image quality loss when regenerating images. If there is no backup changes above can't be undone</Alert>

							</Paper>
						</Grid>
					</Grid>
		} else {
			progressbar = <Box p={1} m={1}>
							<LinearProgress />
						</Box>;
		}

		if ( this.state.completed ) {
			notification = <Alert severity="success">All done! We have tried to repair all images. If you still have issues please check images manually.</Alert>;
		}

		return (
			<div>
				{notification}
				{progressbar}
				{button}
				{this.list_images()}
			</div>
		);
	}
}

export default ListImages;