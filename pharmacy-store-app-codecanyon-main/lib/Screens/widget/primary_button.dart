import 'package:flutter/material.dart';
import '../../constant.dart';

class NewPrimaryButton extends StatelessWidget {
  const NewPrimaryButton({super.key, required this.buttonText, required this.onPressed});
  final String buttonText;
  final VoidCallback onPressed;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: EdgeInsets.zero,
      width: double.infinity,
      decoration: BoxDecoration(
        gradient: kGradiant,
        borderRadius: BorderRadius.circular(30), // Adjust radius as needed
      ),
      child: ElevatedButton(
        onPressed: onPressed,
        child: Text(
          buttonText,
          style: theme.textTheme.bodyLarge?.copyWith(
            fontWeight: FontWeight.w500,
            // fontSize: 18,
            color: Colors.white, // Use a color that contrasts with your gradient
          ),
        ),
      ),
    );
  }
}

///-----------------doted line-------------------
class DottedLineWidget extends StatelessWidget {
  const DottedLineWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: List.generate(60, (index) {
        return Container(
          width: 3, // Width of each dot
          height: 1, // Height of each dot
          decoration: BoxDecoration(
            color: inNutral100, // Color of the dot
          ),
        );
      }),
    );
  }
}
